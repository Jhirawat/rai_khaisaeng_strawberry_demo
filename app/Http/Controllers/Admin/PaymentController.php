<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderWorkflowService;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index()
    {
        return view('admin.payments.index', [
            'payments' => Payment::with('order.user')->latest()->paginate(20),
        ]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($payment, $request): array {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

                if ((int) $lockedPayment->order_id !== (int) $lockedOrder->getKey()) {
                    return ['status' => 'mismatched_order'];
                }

                if (! in_array($lockedPayment->method, ['bank_transfer', 'qr'], true)) {
                    return ['status' => 'invalid_method'];
                }

                if ($lockedPayment->status !== 'pending') {
                    return ['status' => 'already_reviewed'];
                }

                if ($lockedOrder->status !== 'pending_payment') {
                    return ['status' => 'invalid_order'];
                }

                $approvedOrder = $this->workflow->transition(
                    $lockedOrder,
                    'paid',
                    $request->user(),
                    'payment_approval',
                );
                $approvedOrder->forceFill(['payment_status' => 'approved'])->save();
                $lockedPayment->forceFill([
                    'status' => 'approved',
                    'verified_by' => $request->user()?->getKey(),
                    'verified_at' => now(),
                ])->save();

                return [
                    'status' => 'reviewed',
                    'payment' => $lockedPayment->fresh(),
                    'order_number' => $approvedOrder->order_number,
                ];
            });
        } catch (InvalidOrderTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result['status'] === 'already_reviewed') {
            return back()->with('success', 'รายการนี้ถูกตรวจสอบแล้ว');
        }

        if ($result['status'] === 'mismatched_order') {
            return back()->with('error', 'ข้อมูลการชำระเงินไม่ตรงกับคำสั่งซื้อ กรุณาลองใหม่');
        }

        if ($result['status'] === 'invalid_method') {
            return back()->with('error', 'การชำระเงินปลายทางต้องยืนยันผ่านสถานะการจัดส่ง');
        }

        if ($result['status'] === 'invalid_order') {
            return back()->with('error', 'ไม่สามารถตรวจสอบการชำระเงินของคำสั่งซื้อที่สิ้นสุดหรือดำเนินการต่อแล้วได้');
        }

        ActivityLogger::log('payment.approved', $result['payment'], [
            'order' => $result['order_number'],
        ]);

        return back()->with('success', 'อนุมัติการชำระเงินแล้ว');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'reject_reason' => 'nullable|string|max:1000',
        ]);

        $result = DB::transaction(function () use ($data, $payment, $request): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ((int) $lockedPayment->order_id !== (int) $lockedOrder->getKey()) {
                return ['status' => 'mismatched_order'];
            }

            if (! in_array($lockedPayment->method, ['bank_transfer', 'qr'], true)) {
                return ['status' => 'invalid_method'];
            }

            if ($lockedPayment->status !== 'pending') {
                return ['status' => 'already_reviewed'];
            }

            if ($lockedOrder->status !== 'pending_payment') {
                return ['status' => 'invalid_order'];
            }

            $lockedOrder->forceFill(['payment_status' => 'rejected'])->save();
            $lockedPayment->forceFill([
                'status' => 'rejected',
                'reject_reason' => $data['reject_reason'] ?? null,
                'verified_by' => $request->user()?->getKey(),
                'verified_at' => now(),
            ])->save();

            return [
                'status' => 'reviewed',
                'payment' => $lockedPayment->fresh(),
                'order_number' => $lockedOrder->order_number,
            ];
        });

        if ($result['status'] === 'already_reviewed') {
            return back()->with('success', 'รายการนี้ถูกตรวจสอบแล้ว');
        }

        if ($result['status'] === 'mismatched_order') {
            return back()->with('error', 'ข้อมูลการชำระเงินไม่ตรงกับคำสั่งซื้อ กรุณาลองใหม่');
        }

        if ($result['status'] === 'invalid_method') {
            return back()->with('error', 'การชำระเงินปลายทางต้องยืนยันผ่านสถานะการจัดส่ง');
        }

        if ($result['status'] === 'invalid_order') {
            return back()->with('error', 'ไม่สามารถตรวจสอบการชำระเงินของคำสั่งซื้อที่สิ้นสุดหรือดำเนินการต่อแล้วได้');
        }

        ActivityLogger::log('payment.rejected', $result['payment'], [
            'order' => $result['order_number'],
            'reason' => $data['reject_reason'] ?? null,
        ]);

        return back()->with('success', 'ปฏิเสธการชำระเงินแล้ว');
    }
}
