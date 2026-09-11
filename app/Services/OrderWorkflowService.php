<?php

namespace App\Services;

use App\Exceptions\InvalidOrderTransition;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
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

    public function transition(
        Order $order,
        string $to,
        ?User $actor = null,
        string $source = 'system',
    ): Order {
        return DB::transaction(function () use ($order, $to, $actor, $source): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
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
        });
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
