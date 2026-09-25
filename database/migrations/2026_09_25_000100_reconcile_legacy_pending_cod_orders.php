<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Portable SQL: bounded pages and one short transaction per order.
        DB::table('orders')->select('id')->where('status', 'pending_payment')
            ->where('payment_status', 'pending')->chunkById(100, function ($orders): void {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate): void {
                        // Same lock ordering as OrderWorkflowService: order -> shipment -> payment.
                        $order = DB::table('orders')->where('id', $candidate->id)->lockForUpdate()->first();
                        if ($order === null || $order->status !== 'pending_payment' || $order->payment_status !== 'pending'
                            || $order->cancelled_at !== null || $order->stock_returned_at !== null) {
                            return;
                        }
                        $shipments = DB::table('shipments')->where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get();
                        $payments = DB::table('payments')->where('order_id', $order->id)->orderBy('id')->lockForUpdate()->get();
                        if ($shipments->count() !== 1 || $payments->count() !== 1) {
                            return;
                        }
                        $shipment = $shipments->first();
                        $payment = $payments->first();
                        if ($shipment->status !== 'pending' || $shipment->shipped_at !== null || $shipment->delivered_at !== null
                            || $payment->method !== 'cod' || $payment->status !== 'pending'
                            || $payment->verified_by !== null || $payment->verified_at !== null || $payment->reject_reason !== null) {
                            return;
                        }
                        DB::table('orders')->where('id', $order->id)->update(['status' => 'confirmed', 'expires_at' => null]);
                    });
                }
            });
    }

    public function down(): void
    {
        // Forward-only repair: reverting strands COD again and cannot reconstruct expiry.
        // Restore a database backup when an exact data rollback is required.
    }
};
