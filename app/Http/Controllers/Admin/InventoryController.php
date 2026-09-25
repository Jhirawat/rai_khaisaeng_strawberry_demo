<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with('product.category')->orderBy('quantity');
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        }
        if ($request->filled('search')) {
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'));
        }
        $inventories = $query->paginate(20)->withQueryString();
        $summary = [
            'total' => Inventory::count(),
            'low' => Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'out' => Inventory::where('quantity', '<=', 0)->count(),
            'stock' => Inventory::sum('quantity'),
        ];

        return view('admin.inventory.index', compact('inventories', 'summary'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);
        $oldQty = $inventory->quantity;
        $inventory->update([
            'quantity' => $data['quantity'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? $inventory->low_stock_threshold,
        ]);
        InventoryLog::create([
            'inventory_id' => $inventory->id,
            'type' => 'adjust',
            'quantity' => abs($data['quantity'] - $oldQty),
            'note' => $data['note'] ?? 'ปรับสต๊อกจากหน้าแอดมิน',
            'user_id' => auth()->id(),
        ]);
        ActivityLogger::log('inventory.updated', $inventory, ['from' => $oldQty, 'to' => $inventory->quantity], $inventory->product->name ?? 'Inventory #'.$inventory->id);

        return back()->with('success', 'อัปเดตคลังสินค้าเรียบร้อย');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'quantities' => 'required|array',
            'thresholds' => 'required|array',
            'quantities.*' => 'required|integer|min:0',
            'thresholds.*' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['quantities'] as $inventoryId => $quantity) {
                $inventory = Inventory::with('product')->lockForUpdate()->find($inventoryId);
                if (! $inventory) {
                    continue;
                }

                $oldQty = (int) $inventory->quantity;
                $oldThreshold = (int) $inventory->low_stock_threshold;
                $quantity = (int) $quantity;
                $threshold = (int) ($data['thresholds'][$inventoryId] ?? $oldThreshold);
                if ($quantity === $oldQty && $threshold === $oldThreshold) {
                    continue;
                }

                $inventory->update(['quantity' => $quantity, 'low_stock_threshold' => $threshold]);
                InventoryLog::create([
                    'inventory_id' => $inventory->id,
                    'type' => 'adjust',
                    'quantity' => abs($quantity - $oldQty),
                    'note' => $quantity === $oldQty ? 'ปรับจำนวนขั้นต่ำแจ้งเตือนจากหน้าแอดมิน' : 'บันทึกสต๊อกหลายรายการจากหน้าแอดมิน',
                    'user_id' => auth()->id(),
                ]);
                ActivityLogger::log('inventory.bulk_updated', $inventory, [
                    'quantity_from' => $oldQty,
                    'quantity_to' => $quantity,
                    'threshold_from' => $oldThreshold,
                    'threshold_to' => $threshold,
                ], $inventory->product->name ?? 'Inventory #'.$inventory->id);
            }
        });

        return back()->with('success', 'บันทึกคลังสินค้าทั้งหมดเรียบร้อยแล้ว');
    }
}
