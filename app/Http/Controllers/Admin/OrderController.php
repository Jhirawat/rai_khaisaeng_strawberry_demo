<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $base = Order::query();
        $summary = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending_payment')->count(),
            'paid' => (clone $base)->where('status', 'paid')->count(),
            'preparing' => (clone $base)->where('status', 'preparing')->count(),
            'packed' => (clone $base)->where('status', 'packed')->count(),
            'shipping' => (clone $base)->where('status', 'shipped')->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
            'delivery_failed' => (clone $base)->where('status', 'delivery_failed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'sales' => (clone $base)->whereIn('status', ['paid', 'preparing', 'packed', 'shipped', 'delivered'])->sum('total'),
        ];
        $orders = Order::with('user', 'payment', 'shipment')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->date))
            ->when($request->filled('search'), function ($q) use ($request) {
                $keyword = trim($request->search);
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('order_number', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
                });
            })->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders', 'summary'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user', 'payment', 'shipment');

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $r, Order $order)
    {
        $d = $r->validate(['status' => 'required|in:pending_payment,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled']);
        $old = $order->status;
        if ($old === 'cancelled' && $d['status'] !== 'cancelled') {
            return back()->with('error', 'ออเดอร์ที่ยกเลิกและคืนสต๊อกแล้วไม่สามารถเปิดกลับได้ กรุณาสร้างออเดอร์ใหม่');
        }
        DB::transaction(function () use ($order, $d) {
            $order->update(['status' => $d['status'], 'cancelled_at' => $d['status'] === 'cancelled' ? now() : $order->cancelled_at]);
            if ($d['status'] === 'cancelled') {
                $this->returnStockOnce($order);
            }
        });
        ActivityLogger::log('order.status_updated', $order, ['from' => $old, 'to' => $d['status']], $order->order_number);

        return back()->with('success', 'อัปเดตสถานะคำสั่งซื้อแล้ว');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate(['statuses' => 'required|array', 'statuses.*' => 'required|in:pending_payment,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled']);
        foreach ($data['statuses'] as $orderId => $status) {
            $order = Order::with('items.product.inventory')->find($orderId);
            if (! $order) {
                continue;
            }
            $old = $order->status;
            if ($old === 'cancelled' && $status !== 'cancelled') {
                continue;
            }
            DB::transaction(function () use ($order, $status) {
                $order->update(['status' => $status, 'cancelled_at' => $status === 'cancelled' ? now() : $order->cancelled_at]);
                if ($status === 'cancelled') {
                    $this->returnStockOnce($order);
                }
            });
            ActivityLogger::log('order.bulk_status_updated', $order, ['from' => $old, 'to' => $status], $order->order_number);
        }

        return back()->with('success', 'บันทึกสถานะคำสั่งซื้อทั้งหมดเรียบร้อยแล้ว');
    }

    private function returnStockOnce(Order $order): void
    {
        if ($order->stock_returned_at) {
            return;
        }
        $order->loadMissing('items.product.inventory');
        foreach ($order->items as $item) {
            if ($item->product && $item->product->inventory) {
                $item->product->inventory->increment('quantity', (int) $item->quantity);
            }
        }
        $order->forceFill(['stock_returned_at' => now()])->save();
    }
}
