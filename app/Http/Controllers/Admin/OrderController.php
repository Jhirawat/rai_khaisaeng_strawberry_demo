<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $base = Order::query();
        $summary = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status','pending_payment')->count(),
            'paid' => (clone $base)->where('status','paid')->count(),
            'preparing' => (clone $base)->where('status','preparing')->count(),
            'packed' => (clone $base)->where('status','packed')->count(),
            'shipping' => (clone $base)->where('status','shipped')->count(),
            'delivered' => (clone $base)->where('status','delivered')->count(),
            'delivery_failed' => (clone $base)->where('status','delivery_failed')->count(),
            'cancelled' => (clone $base)->where('status','cancelled')->count(),
            'sales' => (clone $base)->whereIn('status',['paid','preparing','packed','shipped','delivered'])->sum('total'),
        ];

        $orders = Order::with('user','payment','shipment')
            ->when($request->filled('status'), fn($q) => $q->where('status',$request->status))
            ->when($request->filled('date'), fn($q) => $q->whereDate('created_at',$request->date))
            ->when($request->filled('search'), function($q) use($request){
                $keyword = trim($request->search);
                $q->where(function($qq) use($keyword){
                    $qq->where('order_number','like',"%{$keyword}%")
                       ->orWhereHas('user', fn($u) => $u->where('name','like',"%{$keyword}%")->orWhere('email','like',"%{$keyword}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index',compact('orders','summary'));
    }

    public function show(Order $order)
    {
        $order->load('items.product','user','payment','shipment');
        return view('admin.orders.show',compact('order'));
    }

    public function update(Request $r,Order $order)
    {
        $d=$r->validate(['status'=>'required|in:pending_payment,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled']);
        $order->update(['status'=>$d['status']]);
        return back()->with('success','อัปเดตสถานะคำสั่งซื้อแล้ว');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:pending_payment,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled',
        ]);

        foreach ($data['statuses'] as $orderId => $status) {
            Order::whereKey($orderId)->update(['status' => $status]);
        }

        return back()->with('success', 'บันทึกสถานะคำสั่งซื้อทั้งหมดเรียบร้อยแล้ว');
    }
}

