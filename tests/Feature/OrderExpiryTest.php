<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_transfer_order_is_cancelled_and_stock_is_restored_once(): void
    {
        $order = $this->createOrder('pending_payment', now()->subMinute());
        Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => $order->total,
            'status' => 'pending',
        ]);
        $inventory = $this->createInventoryForOrder($order, stock: 7, orderedQuantity: 3);

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 1 pending orders; 0 failed.')
            ->assertSuccessful();

        $expiredOrder = $order->fresh();
        $this->assertSame('cancelled', $expiredOrder->status);
        $this->assertNotNull($expiredOrder->stock_returned_at);
        $this->assertNotNull($expiredOrder->cancelled_at);
        $this->assertSame(10, $inventory->fresh()->quantity);
        $this->assertSame(1, InventoryLog::where('inventory_id', $inventory->id)->where('type', 'add')->count());
        $this->assertSame(1, ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('action', 'order.status_transitioned')
            ->count());

        $stockReturnedAt = $expiredOrder->stock_returned_at;

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 0 pending orders; 0 failed.')
            ->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertTrue($stockReturnedAt->equalTo($order->fresh()->stock_returned_at));
        $this->assertSame(10, $inventory->fresh()->quantity);
        $this->assertSame(1, InventoryLog::where('inventory_id', $inventory->id)->where('type', 'add')->count());
        $this->assertSame(1, ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('action', 'order.status_transitioned')
            ->count());
    }

    public function test_old_confirmed_cod_order_without_expiry_is_not_cancelled_or_restocked(): void
    {
        $order = $this->createOrder('confirmed', null, now()->subDay());
        Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->total,
            'status' => 'pending',
        ]);
        $inventory = $this->createInventoryForOrder($order, stock: 7, orderedQuantity: 3);

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 0 pending orders; 0 failed.')
            ->assertSuccessful();

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_returned_at);
        $this->assertNull($order->fresh()->cancelled_at);
        $this->assertSame(7, $inventory->fresh()->quantity);
        $this->assertSame(0, InventoryLog::where('inventory_id', $inventory->id)->where('type', 'add')->count());
    }

    private function createOrder(string $status, mixed $expiresAt, mixed $orderedAt = null): Order
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => uniqid('customer-', true).'@example.test',
            'password' => 'password',
        ]);

        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'EXP-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => $status,
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'expires_at' => $expiresAt,
            'ordered_at' => $orderedAt ?? now(),
        ]);
    }

    private function createInventoryForOrder(Order $order, int $stock, int $orderedQuantity): Inventory
    {
        $category = Category::create([
            'name' => 'Fresh fruit',
            'slug' => 'fresh-fruit-'.bin2hex(random_bytes(4)),
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Strawberries',
            'slug' => 'strawberries-'.bin2hex(random_bytes(4)),
            'price' => 100,
            'sku' => 'SKU-'.strtoupper(bin2hex(random_bytes(4))),
        ]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'quantity' => $stock,
            'low_stock_threshold' => 2,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $orderedQuantity,
            'price' => $product->price,
            'total' => $product->price * $orderedQuantity,
        ]);

        return $inventory;
    }
}
