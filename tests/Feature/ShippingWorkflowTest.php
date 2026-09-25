<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShippingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipped_shipment_transitions_a_packed_order_and_records_the_actor(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('packed');
        $shipment = $this->createShipment($order, 'preparing');
        $this->createCodPayment($order);

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'carrier' => 'Thailand Post',
            'tracking_number' => 'TH123456789',
            'status' => 'shipped',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('shipped', $shipment->fresh()->status);
        $this->assertSame('Thailand Post', $shipment->fresh()->carrier);
        $this->assertSame('TH123456789', $shipment->fresh()->tracking_number);
        $this->assertNotNull($shipment->fresh()->shipped_at);

        $activity = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->sole();
        $this->assertSame($admin->id, $activity->user_id);
        $this->assertSame('shipping_update', $activity->properties['source']);
    }

    #[DataProvider('ordersThatCannotBeShipped')]
    public function test_invalid_shipped_transition_leaves_order_and_shipment_unchanged(string $orderStatus): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder($orderStatus);
        $shipment = $this->createShipment($order, 'pending');
        $payment = $this->createCodPayment($order);
        $originalUpdatedAt = $shipment->updated_at;

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'carrier' => 'Mutated Carrier',
            'tracking_number' => 'MUTATED-TRACKING',
            'status' => 'shipped',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertSame($orderStatus, $order->fresh()->status);
        $this->assertSame('pending', $shipment->fresh()->status);
        $this->assertNull($shipment->fresh()->carrier);
        $this->assertNull($shipment->fresh()->tracking_number);
        $this->assertNull($shipment->fresh()->shipped_at);
        $this->assertTrue($originalUpdatedAt->equalTo($shipment->fresh()->updated_at));
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->verified_by);
        $this->assertNull($payment->fresh()->verified_at);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_delivered_cod_shipment_approves_pending_payment_atomically(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('shipped');
        $shipment = $this->createShipment($order, 'shipped');
        $payment = $this->createCodPayment($order);

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'status' => 'delivered',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('approved', $order->fresh()->payment_status);
        $this->assertSame('delivered', $shipment->fresh()->status);
        $this->assertNotNull($shipment->fresh()->delivered_at);
        $this->assertSame('approved', $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);
    }

    public function test_returned_cod_shipment_rejects_pending_payment_atomically(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('shipped');
        $shipment = $this->createShipment($order, 'shipped');
        $payment = $this->createCodPayment($order);

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'status' => 'returned',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('delivery_failed', $order->fresh()->status);
        $this->assertSame('rejected', $order->fresh()->payment_status);
        $this->assertSame('returned', $shipment->fresh()->status);
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);
    }

    public function test_same_state_shipped_metadata_edit_preserves_the_original_shipped_time(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('shipped');
        $shipment = $this->createShipment($order, 'shipped');
        $this->createCodPayment($order);
        $originalShippedAt = $shipment->fresh()->shipped_at;

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'carrier' => 'Updated Carrier',
            'tracking_number' => 'UPDATED-TRACKING',
            'status' => 'shipped',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('Updated Carrier', $shipment->fresh()->carrier);
        $this->assertSame('UPDATED-TRACKING', $shipment->fresh()->tracking_number);
        $this->assertTrue($originalShippedAt->equalTo($shipment->fresh()->shipped_at));
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_same_state_delivered_metadata_edit_preserves_all_shipping_times(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('delivered');
        $order->update(['payment_status' => 'approved']);
        $shipment = $this->createShipment($order, 'delivered');
        $this->createCodPayment($order, 'approved');
        $originalShippedAt = now()->subDays(3);
        $originalDeliveredAt = now()->subDays(2);
        $shipment->forceFill([
            'shipped_at' => $originalShippedAt,
            'delivered_at' => $originalDeliveredAt,
        ])->save();
        $shipment->refresh();
        $originalShippedAt = $shipment->shipped_at;
        $originalDeliveredAt = $shipment->delivered_at;

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'carrier' => 'Updated Carrier',
            'tracking_number' => 'UPDATED-DELIVERED',
            'status' => 'delivered',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertTrue($originalShippedAt->equalTo($shipment->fresh()->shipped_at));
        $this->assertTrue($originalDeliveredAt->equalTo($shipment->fresh()->delivered_at));
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_reshipping_a_returned_shipment_records_a_new_shipped_time(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('delivery_failed');
        $order->update(['payment_status' => 'rejected']);
        $shipment = $this->createShipment($order, 'returned');
        $this->createCodPayment($order, 'rejected');
        $originalShippedAt = now()->subDays(2);
        $shipment->forceFill(['shipped_at' => $originalShippedAt])->save();

        $response = $this->actingAs($admin)->patch(route('admin.shipping.update', $shipment), [
            'carrier' => 'Retry Carrier',
            'tracking_number' => 'RETRY-TRACKING',
            'status' => 'shipped',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('shipped', $shipment->fresh()->status);
        $this->assertTrue($shipment->fresh()->shipped_at->greaterThan($originalShippedAt));
    }

    public static function ordersThatCannotBeShipped(): array
    {
        return [
            'pending payment order' => ['pending_payment'],
            'cancelled order' => ['cancelled'],
            'delivered order' => ['delivered'],
        ];
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Shipping Admin',
            'email' => uniqid('shipping-admin-', true).'@example.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createOrder(string $status): Order
    {
        $customer = User::create([
            'name' => 'Customer',
            'email' => uniqid('customer-', true).'@example.test',
            'password' => 'password',
        ]);

        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'SHIP-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => $status,
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
        ]);
    }

    private function createShipment(Order $order, string $status): Shipment
    {
        return Shipment::create([
            'order_id' => $order->id,
            'status' => $status,
            'shipped_at' => $status === 'shipped' ? now()->subDay() : null,
        ]);
    }

    private function createCodPayment(Order $order, string $status = 'pending'): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->total,
            'status' => $status,
        ]);
    }
}
