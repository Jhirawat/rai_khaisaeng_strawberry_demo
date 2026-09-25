<?php

namespace App\Services;

use App\Exceptions\InvalidOrderTransition;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    private const EXPIRABLE_PAYMENT_METHODS = ['bank_transfer', 'qr'];

    private const SHIPMENT_TARGETS = [
        'preparing' => 'preparing',
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'returned' => 'delivery_failed',
    ];

    private const TRANSITIONS = [
        'pending_payment' => ['paid', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'paid' => ['preparing', 'cancelled'],
        'preparing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'delivery_failed'],
        'delivery_failed' => ['shipped', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function allowedTargets(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function expirablePaymentMethods(): array
    {
        return self::EXPIRABLE_PAYMENT_METHODS;
    }

    public function adminTargets(Order $order): array
    {
        $targets = $this->canFulfill($order, $order->payment) ? ['packed', 'cancelled'] : ['cancelled'];

        return array_values(array_intersect($this->allowedTargets($order->status), $targets));
    }

    public function shipmentStatuses(): array
    {
        return array_keys(self::SHIPMENT_TARGETS);
    }

    public function shipmentTargets(Order $order): array
    {
        if (! $this->canFulfill($order, $order->payment)) {
            return [];
        }

        return array_keys(array_filter(self::SHIPMENT_TARGETS, fn (string $target): bool => in_array($target, $this->allowedTargets($order->status), true)
            || ($target === $order->status && $order->shipment?->status === array_search($target, self::SHIPMENT_TARGETS, true))));
    }

    public function updateShipment(Shipment $shipment, array $data, ?User $actor): void
    {
        DB::transaction(function () use ($shipment, $data, $actor): void {
            // Existing-order operations lock order -> shipment (if needed) -> payment.
            // Recheck association after locking; never acquire another order afterwards.
            $order = Order::query()->lockForUpdate()->findOrFail($shipment->order_id);
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->getKey());
            $to = self::SHIPMENT_TARGETS[$data['status']] ?? null;
            if ((int) $lockedShipment->order_id !== (int) $order->getKey() || $to === null) {
                throw new InvalidOrderTransition($order->status, $data['status']);
            }
            $payment = $order->payment()->lockForUpdate()->first();
            if (! $this->canFulfill($order, $payment)) {
                throw new InvalidOrderTransition($order->status, $to);
            }
            $this->transitionLocked($order, $to, $actor, 'shipping_update');
            $changed = $lockedShipment->status !== $data['status'];
            $lockedShipment->fill($data);
            if ($changed && $data['status'] === 'shipped') {
                $lockedShipment->shipped_at = now();
            }
            if ($changed && $data['status'] === 'delivered') {
                $lockedShipment->delivered_at = now();
            }
            if ($lockedShipment->isDirty()) {
                $lockedShipment->save();
            }

            $paymentStatus = match ($data['status']) {
                'delivered' => 'approved',
                'returned' => 'rejected',
                default => null,
            };
            if (! $changed || $payment?->method !== 'cod' || $paymentStatus === null
                || ! in_array($payment->status, ['pending', 'rejected'], true)
                || $payment->status === $paymentStatus) {
                return;
            }
            $previous = $payment->status;
            $payment->forceFill(['status' => $paymentStatus, 'verified_by' => $actor?->getKey(), 'verified_at' => now()])->save();
            $order->forceFill(['payment_status' => $paymentStatus])->save();
            ActivityLog::create([
                'user_id' => $actor?->getKey(),
                'action' => 'payment.cod_settled',
                'subject_type' => Payment::class,
                'subject_id' => $payment->getKey(),
                'subject_label' => $order->order_number,
                'properties' => ['from' => $previous, 'to' => $paymentStatus, 'source' => 'shipping_update'],
            ]);
        });
    }

    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($lockedOrder->status !== 'pending_payment'
                || ! in_array($lockedOrder->payment_status, ['pending', 'rejected'], true)
                || $lockedOrder->expires_at === null
                || ! $lockedOrder->expires_at->lt(now())) {
                return false;
            }

            $payment = $lockedOrder->payment()->lockForUpdate()->first();

            if ($payment === null
                || ! in_array($payment->status, ['pending', 'rejected'], true)
                || ! in_array($payment->method, self::EXPIRABLE_PAYMENT_METHODS, true)) {
                return false;
            }

            $this->transitionLocked($lockedOrder, 'cancelled', null, 'order_expiry');

            return true;
        });
    }

    public function transition(
        Order $order,
        string $to,
        ?User $actor = null,
        string $source = 'system',
    ): Order {
        return DB::transaction(function () use ($order, $to, $actor, $source): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            // Shipment-owned changes must use updateShipment, including internal callers.
            if ($to !== $lockedOrder->status && in_array($to, self::SHIPMENT_TARGETS, true)) {
                throw new InvalidOrderTransition($lockedOrder->status, $to);
            }
            if ($to !== $lockedOrder->status && $to === 'packed'
                && ! $this->canFulfill($lockedOrder, $lockedOrder->payment()->lockForUpdate()->first())) {
                throw new InvalidOrderTransition($lockedOrder->status, $to);
            }
            if ($to !== $lockedOrder->status && $to === 'paid') {
                $payment = $lockedOrder->payment()->lockForUpdate()->first();
                if ($source !== 'payment_approval' || $lockedOrder->payment_status !== 'approved'
                    || $payment?->status !== 'approved'
                    || ! in_array($payment->method, self::EXPIRABLE_PAYMENT_METHODS, true)) {
                    throw new InvalidOrderTransition($lockedOrder->status, $to);
                }
            }

            return $this->transitionLocked($lockedOrder, $to, $actor, $source);
        });
    }

    private function transitionLocked(Order $lockedOrder, string $to, ?User $actor, string $source): Order
    {
        $from = $lockedOrder->status;

        if ($from === $to) {
            return $lockedOrder;
        }

        if (! in_array($to, $this->allowedTargets($from), true)) {
            throw new InvalidOrderTransition($from, $to);
        }

        if ($to === 'cancelled') {
            $this->restoreStockOnce($lockedOrder, $actor, $source);
        }

        $changes = ['status' => $to];
        if ($to === 'cancelled') {
            $changes['cancelled_at'] = now();
        }

        $lockedOrder->forceFill($changes)->save();

        ActivityLog::create([
            'user_id' => $actor?->getKey(),
            'action' => 'order.status_transitioned',
            'subject_type' => Order::class,
            'subject_id' => $lockedOrder->getKey(),
            'subject_label' => $lockedOrder->order_number,
            'properties' => [
                'from' => $from,
                'to' => $to,
                'source' => $source,
            ],
        ]);

        return $lockedOrder->fresh();
    }

    private function canFulfill(Order $order, ?Payment $payment): bool
    {
        if ($payment === null) {
            return false;
        }

        if ($payment->method === 'cod') {
            return in_array($payment->status, ['pending', 'rejected', 'approved'], true)
                && $order->payment_status === $payment->status;
        }

        return in_array($payment->method, self::EXPIRABLE_PAYMENT_METHODS, true)
            && $payment->status === 'approved'
            && $order->payment_status === 'approved';
    }

    private function restoreStockOnce(Order $order, ?User $actor, string $source): void
    {
        if ($order->stock_returned_at !== null) {
            return;
        }

        foreach ($order->items()->get() as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $inventory = Inventory::query()
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                continue;
            }

            $quantity = (int) $item->quantity;
            $inventory->increment('quantity', $quantity);
            InventoryLog::create([
                'inventory_id' => $inventory->getKey(),
                'type' => 'add',
                'quantity' => $quantity,
                'note' => sprintf(
                    'คืนสต๊อกจากคำสั่งซื้อ %s (%s)',
                    $order->order_number,
                    $source,
                ),
                'user_id' => $actor?->getKey(),
            ]);
        }

        $order->forceFill(['stock_returned_at' => now()])->save();
    }
}
