<?php

namespace Tests\Unit;

use App\Exceptions\InvalidOrderTransition;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TRANSITIONS = [
        'pending_payment' => ['paid', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'paid' => ['preparing', 'cancelled'],
        'preparing' => ['packed', 'cancelled'],
        'packed' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'delivery_failed'],
        'delivery_failed' => ['shipped', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function test_allowed_targets_match_the_order_lifecycle(): void
    {
        $this->assertSame(self::TRANSITIONS, collect(array_keys(self::TRANSITIONS))
            ->mapWithKeys(fn (string $status): array => [
                $status => app(OrderWorkflowService::class)->allowedTargets($status),
            ])->all());
    }

    public function test_confirmed_has_bilingual_labels_and_an_admin_badge(): void
    {
        app()->setLocale('en');
        $this->assertSame('Confirmed', Order::statusLabels()['confirmed'] ?? null);

        app()->setLocale('th');
        $this->assertSame('ยืนยันแล้ว', Order::statusLabels()['confirmed'] ?? null);

        $order = new Order(['status' => 'confirmed']);
        $this->assertSame('text-bg-primary', $order->status_badge_class);
    }

    #[DataProvider('allowedTransitions')]
    public function test_each_allowed_transition_updates_the_stored_order(string $from, string $to): void
    {
        $order = $this->createOrder($from);

        $workflow = app(OrderWorkflowService::class);
        if ($to === 'paid') {
            $order->update(['payment_status' => 'approved']);
            Payment::create(['order_id' => $order->id, 'method' => 'bank_transfer', 'status' => 'approved', 'amount' => $order->total]);
            $transitioned = $workflow->transition($order, $to, source: 'payment_approval');
        } else {
            if (in_array($to, ['preparing', 'packed', 'shipped', 'delivered', 'delivery_failed'], true)) {
                $paymentStatus = $from === 'delivery_failed' ? 'rejected' : ($from === 'paid' ? 'approved' : 'pending');
                $paymentMethod = $from === 'paid' ? 'bank_transfer' : 'cod';
                $order->update(['payment_status' => $paymentStatus]);
                Payment::create(['order_id' => $order->id, 'method' => $paymentMethod, 'status' => $paymentStatus, 'amount' => $order->total]);
            }
            if (in_array($to, ['preparing', 'shipped', 'delivered', 'delivery_failed'], true)) {
                $shipmentStatus = match ($from) {
                    'packed' => 'preparing',
                    'shipped' => 'shipped',
                    'delivery_failed' => 'returned',
                    default => 'pending',
                };
                $shipment = Shipment::create(['order_id' => $order->id, 'status' => $shipmentStatus]);
                $workflow->updateShipment($shipment, ['status' => $to === 'delivery_failed' ? 'returned' : $to], null);
                $transitioned = $order->fresh();
            } else {
                $transitioned = $workflow->transition($order, $to);
            }
        }

        $this->assertSame($to, $transitioned->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $to,
        ]);
    }

    #[DataProvider('invalidTransitions')]
    public function test_backward_or_skipped_transitions_are_rejected_without_changing_the_order(
        string $from,
        string $to,
    ): void {
        $order = $this->createOrder($from);

        try {
            app(OrderWorkflowService::class)->transition($order, $to);
            $this->fail("Expected transition from {$from} to {$to} to be rejected.");
        } catch (InvalidOrderTransition $exception) {
            $this->assertStringContainsString($from, $exception->getMessage());
            $this->assertStringContainsString($to, $exception->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $exception->getMessage());
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => $from,
        ]);
    }

    public function test_repeating_cancellation_restores_stock_and_writes_inventory_history_once(): void
    {
        $actor = $this->createUser('operator@example.test');
        $order = $this->createOrder('pending_payment');
        $inventory = $this->createInventoryForOrder($order, stock: 7, orderedQuantity: 3);
        $service = app(OrderWorkflowService::class);

        $cancelled = $service->transition($order, 'cancelled', $actor, 'admin_order_update');
        $retried = $service->transition($order, 'cancelled', $actor, 'admin_order_update');

        $this->assertSame('cancelled', $retried->status);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertNotNull($cancelled->stock_returned_at);
        $this->assertSame(10, $inventory->fresh()->quantity);
        $this->assertSame(1, InventoryLog::where('inventory_id', $inventory->id)->where('type', 'add')->count());
        $this->assertDatabaseHas('inventory_logs', [
            'inventory_id' => $inventory->id,
            'type' => 'add',
            'quantity' => 3,
            'user_id' => $actor->id,
        ]);
        $activity = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->sole();
        $this->assertSame($actor->id, $activity->user_id);
        $this->assertSame('order.status_transitioned', $activity->action);
        $this->assertSame([
            'from' => 'pending_payment',
            'to' => 'cancelled',
            'source' => 'admin_order_update',
        ], $activity->properties);
    }

    public static function allowedTransitions(): array
    {
        $cases = [];

        foreach (self::TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                $cases["{$from} to {$to}"] = [$from, $to];
            }
        }

        return $cases;
    }

    public static function invalidTransitions(): array
    {
        return [
            'pending payment cannot skip to shipped' => ['pending_payment', 'shipped'],
            'confirmed cannot move backward to pending payment' => ['confirmed', 'pending_payment'],
            'preparing cannot move backward to paid' => ['preparing', 'paid'],
            'packed cannot skip to delivered' => ['packed', 'delivered'],
            'shipped cannot move backward to packed' => ['shipped', 'packed'],
            'delivered is terminal' => ['delivered', 'cancelled'],
            'cancelled is terminal' => ['cancelled', 'pending_payment'],
        ];
    }

    private function createOrder(string $status): Order
    {
        $user = $this->createUser(uniqid('customer-', true).'@example.test');

        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => $status,
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
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

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
