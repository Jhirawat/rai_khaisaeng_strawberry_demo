<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShippingController extends Controller
{
    private const ORDER_STATUS_BY_SHIPMENT_STATUS = [
        'preparing' => 'preparing',
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'returned' => 'delivery_failed',
    ];

    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(self::ORDER_STATUS_BY_SHIPMENT_STATUS))],
        ]);

        try {
            DB::transaction(function () use ($data, $request, $shipment): void {
                $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->getKey());
                $order = $lockedShipment->order()->firstOrFail();
                $order = $this->workflow->transition(
                    $order,
                    self::ORDER_STATUS_BY_SHIPMENT_STATUS[$data['status']],
                    $request->user(),
                    'shipping_update',
                );

                $lockedShipment->fill($data);

                if ($data['status'] === 'shipped') {
                    $lockedShipment->shipped_at = now();
                }

                if ($data['status'] === 'delivered') {
                    $lockedShipment->delivered_at = now();
                }

                $lockedShipment->save();
                $this->settleCodPayment($order, $data['status'], $request->user());
            });
        } catch (InvalidOrderTransition $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'อัปเดตสถานะการจัดส่งแล้ว');
    }

    private function settleCodPayment(Order $order, string $shipmentStatus, ?User $actor): void
    {
        $paymentStatus = match ($shipmentStatus) {
            'delivered' => 'approved',
            'returned' => 'rejected',
            default => null,
        };

        if ($paymentStatus === null) {
            return;
        }

        $payment = Payment::query()
            ->where('order_id', $order->getKey())
            ->where('method', 'cod')
            ->lockForUpdate()
            ->first();

        if ($payment === null || $payment->status !== 'pending') {
            return;
        }

        $payment->forceFill([
            'status' => $paymentStatus,
            'verified_by' => $actor?->getKey(),
            'verified_at' => now(),
        ])->save();

        $order->forceFill(['payment_status' => $paymentStatus])->save();
    }
}
