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
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_transfer_and_qr_orders_expire_and_restore_stock_exactly_once(): void
    {
        $admin = $this->createCustomer();
        $admin->update(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);
        foreach (['bank_transfer', 'qr'] as $method) {
            $order = $this->createPendingTransferOrder();
            $order->payment->update(['method' => $method]);
            $inventory = $this->createInventoryForOrder($order, 7, 3);
            $this->post(route('admin.payments.reject', $order->payment))->assertSessionHas('success');
            $this->artisan('orders:expire')->expectsOutput('Expired 1 pending orders; 0 failed.')->assertSuccessful();
            $this->assertFalse(app(OrderWorkflowService::class)->expire($order));
            $this->post(route('admin.payments.approve', $order->payment));
            $this->assertSame('cancelled', $order->fresh()->status);
            $this->assertSame('rejected', $order->payment->fresh()->status);
            $this->assertSame(10, $inventory->fresh()->quantity);
            $this->assertSame(1, InventoryLog::where('inventory_id', $inventory->id)->count());
            $this->assertSame(1, ActivityLog::where('subject_type', Order::class)->where('subject_id', $order->id)->count());
        }
    }

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

    public function test_legacy_expired_pending_payment_cod_order_is_not_cancelled_or_restocked(): void
    {
        $order = $this->createOrder('pending_payment', now()->subMinute());
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

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_returned_at);
        $this->assertNull($order->fresh()->cancelled_at);
        $this->assertSame(7, $inventory->fresh()->quantity);
        $this->assertDatabaseCount('inventory_logs', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_expiry_rechecks_order_state_after_candidate_selection(): void
    {
        $order = $this->createPendingTransferOrder();
        $inventory = $this->createInventoryForOrder($order, stock: 7, orderedQuantity: 3);
        $candidate = Order::query()->findOrFail($order->id);

        $order->forceFill([
            'status' => 'paid',
            'payment_status' => 'approved',
        ])->save();
        $order->payment->forceFill(['status' => 'approved'])->save();

        $expired = app(OrderWorkflowService::class)->expire($candidate);

        $this->assertFalse($expired);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('approved', $order->fresh()->payment_status);
        $this->assertSame(7, $inventory->fresh()->quantity);
        $this->assertNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseCount('inventory_logs', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_expiry_rechecks_approved_payment_after_selecting_a_rejected_candidate(): void
    {
        $order = $this->createPendingTransferOrder();
        $order->update(['payment_status' => 'rejected']);
        $order->payment->update(['status' => 'rejected']);
        $candidate = $order->fresh();
        $inventory = $this->createInventoryForOrder($order, 7, 3);
        // Even an inconsistent legacy order field must not override approved payment truth.
        $order->payment->update(['status' => 'approved']);
        $this->assertFalse(app(OrderWorkflowService::class)->expire($candidate));
        $this->artisan('orders:expire')->expectsOutput('Expired 0 pending orders; 0 failed.')->assertSuccessful();
        $this->assertSame(7, $inventory->fresh()->quantity);
        $this->assertNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseCount('inventory_logs', 0);
    }

    public function test_expiry_rechecks_payment_method_after_candidate_selection(): void
    {
        $order = $this->createPendingTransferOrder();
        $candidate = Order::query()->findOrFail($order->id);
        $order->payment->forceFill(['method' => 'cod'])->save();

        $expired = app(OrderWorkflowService::class)->expire($candidate);

        $this->assertFalse($expired);
        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_returned_at);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_expiry_processes_orders_across_the_one_hundred_row_chunk_boundary(): void
    {
        $customer = $this->createCustomer();

        foreach (range(1, 101) as $index) {
            $order = $this->createOrder('pending_payment', now()->subMinute(), customer: $customer);
            Payment::create([
                'order_id' => $order->id,
                'method' => $index % 2 === 0 ? 'qr' : 'bank_transfer',
                'amount' => $order->total,
                'status' => 'pending',
            ]);
        }

        $this->artisan('orders:expire')
            ->expectsOutput('Expired 101 pending orders; 0 failed.')
            ->assertSuccessful();

        $this->assertSame(101, Order::where('status', 'cancelled')->count());
        $this->assertSame(101, Order::whereNotNull('stock_returned_at')->count());
        $this->assertSame(101, ActivityLog::where('action', 'order.status_transitioned')->count());
    }

    public function test_expiry_continues_after_one_order_fails_and_returns_a_failure_exit_code(): void
    {
        $failedOrder = $this->createPendingTransferOrder();
        $laterOrder = $this->createPendingTransferOrder();
        $workflow = new class($failedOrder->id) extends OrderWorkflowService
        {
            public function __construct(private readonly int $failedOrderId) {}

            public function expire(Order $order): bool
            {
                if ($order->id === $this->failedOrderId) {
                    throw new RuntimeException('Controlled expiry failure.');
                }

                return parent::expire($order);
            }
        };
        $this->app->instance(OrderWorkflowService::class, $workflow);

        $this->artisan('orders:expire')
            ->expectsOutput("Failed to expire order {$failedOrder->id}.")
            ->expectsOutput('Expired 1 pending orders; 1 failed.')
            ->assertFailed();

        $this->assertSame('pending_payment', $failedOrder->fresh()->status);
        $this->assertSame('cancelled', $laterOrder->fresh()->status);
        $this->assertSame(1, ActivityLog::where('action', 'order.status_transitioned')->count());
    }

    private function createPendingTransferOrder(): Order
    {
        $order = $this->createOrder('pending_payment', now()->subMinute());
        Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        return $order;
    }

    private function createOrder(
        string $status,
        mixed $expiresAt,
        mixed $orderedAt = null,
        ?User $customer = null,
    ): Order {
        $customer ??= $this->createCustomer();

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

    private function createCustomer(): User
    {
        return User::create([
            'name' => 'Customer',
            'email' => uniqid('customer-', true).'@example.test',
            'password' => 'password',
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
