<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
class PaymentController extends Controller{
 public function index(){return view('admin.payments.index',['payments'=>Payment::with('order.user')->latest()->paginate(20)]);} 
 public function approve(Payment $payment){
  if(in_array($payment->status,['approved','rejected'],true)) return back()->with('success','รายการนี้ถูกตรวจสอบแล้ว');
  $payment->update(['status'=>'approved','verified_by'=>auth()->id(),'verified_at'=>now()]);
  $payment->order->update(['payment_status'=>'approved','status'=>'paid']);
  ActivityLogger::log('payment.approved', $payment, ['order'=>$payment->order->order_number ?? $payment->order_id]);
  return back()->with('success','อนุมัติการชำระเงินแล้ว');
 }
 public function reject(Request $r,Payment $payment){
  $d=$r->validate(['reject_reason'=>'nullable|string|max:1000']);
  if(in_array($payment->status,['approved','rejected'],true)) return back()->with('success','รายการนี้ถูกตรวจสอบแล้ว');
  $payment->update(['status'=>'rejected','reject_reason'=>$d['reject_reason'] ?? null,'verified_by'=>auth()->id(),'verified_at'=>now()]);
  $payment->order->update(['payment_status'=>'rejected']);
  ActivityLogger::log('payment.rejected', $payment, ['order'=>$payment->order->order_number ?? $payment->order_id, 'reason'=>$d['reject_reason'] ?? null]);
  return back()->with('success','ปฏิเสธการชำระเงินแล้ว');
 }
}
