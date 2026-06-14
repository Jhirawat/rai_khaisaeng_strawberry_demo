<?php
namespace App\Http\Controllers\Member;
use App\Http\Controllers\Controller; use App\Models\Order;
class OrderController extends Controller{ public function index(){ $orders=Order::with('payment','shipment')->where('user_id',auth()->id())->latest()->paginate(10); return view('member.orders',compact('orders')); } public function show(Order $order){abort_unless($order->user_id===auth()->id(),403); $order->load('items','payment','shipment'); return view('member.order_show',compact('order'));}}
