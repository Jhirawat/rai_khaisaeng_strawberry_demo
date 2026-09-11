<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderWorkflowService $workflow) {}

    public function index(Request $request)
    {
        $base = Order::query();
        $summary = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending_payment')->count(),
            'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
            'paid' => (clone $base)->where('status', 'paid')->count(),
            'preparing' => (clone $base)->where('status', 'preparing')->count(),
            'packed' => (clone $base)->where('status', 'packed')->count(),
            'shipping' => (clone $base)->where('status', 'shipped')->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
            'delivery_failed' => (clone $base)->where('status', 'delivery_failed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'sales' => (clone $base)->whereIn('status', ['confirmed', 'paid', 'preparing', 'packed', 'shipped', 'delivered'])->sum('total'),
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
        $statusOptions = $orders->getCollection()
            ->mapWithKeys(fn (Order $order): array => [
                $order->getKey() => array_values(array_unique([
                    $order->status,
                    ...$this->workflow->allowedTargets($order->status),
                ])),
            ])->all();

        return view('admin.orders.index', compact('orders', 'summary', 'statusOptions'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user', 'payment', 'shipment');
        $statusOptions = array_values(array_unique([
            $order->status,
            ...$this->workflow->allowedTargets($order->status),
        ]));

        return view('admin.orders.show', compact('order', 'statusOptions'));
    }

    public function update(Request $r, Order $order)
    {
        $data = $r->validate([
            'status' => 'required|in:pending_payment,confirmed,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled',
        ]);

        try {
            $this->workflow->transition($order, $data['status'], $r->user(), 'admin_order_update');
        } catch (InvalidOrderTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'อัปเดตสถานะคำสั่งซื้อแล้ว');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'statuses' => 'required|array',
            'statuses.*' => 'required|in:pending_payment,confirmed,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled',
        ]);
        $errors = [];

        foreach ($data['statuses'] as $orderId => $status) {
            $order = Order::find($orderId);
            if (! $order) {
                continue;
            }

            try {
                $this->workflow->transition($order, $status, $request->user(), 'admin_order_bulk_update');
            } catch (InvalidOrderTransition $exception) {
                $errors[] = $order->order_number.': '.$exception->getMessage();
            }
        }

        if ($errors !== []) {
            return back()->with('error', implode(' ', $errors));
        }

        return back()->with('success', 'บันทึกสถานะคำสั่งซื้อที่แก้ไขเรียบร้อยแล้ว');
    }
}
