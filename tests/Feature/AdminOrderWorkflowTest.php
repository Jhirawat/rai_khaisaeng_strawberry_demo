<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_update_uses_the_workflow_and_rejects_a_backward_transition(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('preparing');

        $response = $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'paid',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_single_update_records_a_service_transition_with_the_admin_as_actor(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('confirmed');

        $response = $this->actingAs($admin)->patch(route('admin.orders.update', $order), [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('cancelled', $order->fresh()->status);
        $activity = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->sole();
        $this->assertSame($admin->id, $activity->user_id);
        $this->assertSame('order.status_transitioned', $activity->action);
        $this->assertSame('admin_order_update', $activity->properties['source']);
    }

    public function test_bulk_update_applies_allowed_rows_and_reports_disallowed_rows(): void
    {
        $admin = $this->createAdmin();
        $allowedOrder = $this->createOrder('pending_payment');
        $terminalOrder = $this->createOrder('delivered');

        $response = $this->actingAs($admin)->patch(route('admin.orders.bulkUpdate'), [
            'statuses' => [
                $allowedOrder->id => 'cancelled',
                $terminalOrder->id => 'pending_payment',
            ],
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertSame('cancelled', $allowedOrder->fresh()->status);
        $this->assertSame('delivered', $terminalOrder->fresh()->status);
        $activity = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $allowedOrder->id)
            ->sole();
        $this->assertSame('admin_order_bulk_update', $activity->properties['source']);
    }

    public function test_order_page_offers_only_the_current_and_allowed_target_statuses(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrder('packed');
        Payment::create(['order_id' => $order->id, 'method' => 'cod', 'status' => 'pending', 'amount' => $order->total]);
        Shipment::create(['order_id' => $order->id, 'status' => 'preparing']);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('value="packed"', false);
        $response->assertSee('value="shipped"', false);
        $response->assertSee('value="cancelled"', false);
        $response->assertDontSee('value="paid"', false);
        $response->assertDontSee('value="delivered"', false);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => uniqid('admin-', true).'@example.test',
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
            'order_number' => 'TEST-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => $status,
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
        ]);
    }
}
