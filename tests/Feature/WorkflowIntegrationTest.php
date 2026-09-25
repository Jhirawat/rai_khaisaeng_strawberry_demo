<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unpaidReviews')]
    public function test_generic_single_and_bulk_updates_cannot_approve_payments(string $method, string $paymentStatus): void
    {
        $order = $this->order('pending_payment', $method, $paymentStatus);
        $this->actingAs($this->admin());
        $this->patch(route('admin.orders.update', $order), ['status' => 'paid'])->assertSessionHas('error');
        $this->patch(route('admin.orders.bulkUpdate'), ['statuses' => [$order->id => 'paid']])->assertSessionHas('error');
        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame($paymentStatus, $order->fresh()->payment_status);
        $this->assertSame($paymentStatus, $order->payment->fresh()->status);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('value="paid"', false);
        $this->get(route('admin.orders.index'))->assertOk()->assertDontSee('value="paid"', false);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function unpaidReviews(): array
    {
        return [['bank_transfer', 'pending'], ['bank_transfer', 'rejected'], ['qr', 'pending'], ['qr', 'rejected']];
    }

    #[DataProvider('shipmentEdges')]
    public function test_generic_single_and_bulk_updates_cannot_bypass_shipping(string $from, string $to): void
    {
        $order = $this->order($from);
        $this->actingAs($this->admin());
        $this->patch(route('admin.orders.update', $order), ['status' => $to])->assertSessionHas('error');
        $this->patch(route('admin.orders.bulkUpdate'), ['statuses' => [$order->id => $to]])->assertSessionHas('error');
        $this->assertSame($from, $order->fresh()->status);
        $this->assertSame('pending', $order->payment->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function shipmentEdges(): array
    {
        return [['confirmed', 'preparing'], ['paid', 'preparing'], ['packed', 'shipped'], ['shipped', 'delivered'], ['shipped', 'delivery_failed'], ['delivery_failed', 'shipped']];
    }

    public function test_generic_packing_cannot_progress_a_legacy_unapproved_transfer(): void
    {
        $order = $this->order('preparing', 'bank_transfer', 'rejected');
        $this->actingAs($this->admin());
        $this->patch(route('admin.orders.update', $order), ['status' => 'packed'])->assertSessionHas('error');
        $this->patch(route('admin.orders.bulkUpdate'), ['statuses' => [$order->id => 'packed']])->assertSessionHas('error');
        $this->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('value="packed"', false);
        $this->assertSame('preparing', $order->fresh()->status);
    }

    public function test_generic_fulfillment_fails_closed_without_a_payment_record(): void
    {
        $order = $this->order('preparing');
        $order->payment()->delete();
        $this->actingAs($this->admin());

        $this->patch(route('admin.orders.update', $order), ['status' => 'packed'])->assertSessionHas('error');
        $this->patch(route('admin.orders.bulkUpdate'), ['statuses' => [$order->id => 'packed']])->assertSessionHas('error');
        $this->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('value="packed"', false);

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_shipment_fulfillment_fails_closed_for_a_refunded_cod_payment(): void
    {
        $order = $this->order('packed', 'cod', 'refunded');
        $this->actingAs($this->admin());

        $this->patch(route('admin.shipping.update', $order->shipment), ['status' => 'shipped'])->assertSessionHas('error');
        $this->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('value="shipped"', false);

        $this->assertSame('packed', $order->fresh()->status);
        $this->assertSame('pending', $order->shipment->fresh()->status);
        $this->assertSame('refunded', $order->payment->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_rendered_cod_and_approved_transfer_flows_keep_shipment_and_payment_synchronized(): void
    {
        $this->actingAs($this->admin());
        foreach (['cod', 'bank_transfer'] as $method) {
            $order = $this->order($method === 'cod' ? 'confirmed' : 'pending_payment', $method);
            if ($method !== 'cod') {
                $this->post(route('admin.payments.approve', $order->payment))->assertSessionHas('success');
            }
            foreach (['preparing', 'packed', 'shipped', 'delivered'] as $status) {
                $response = $this->get(route('admin.orders.show', $order))->assertOk();
                $action = $status === 'packed' ? route('admin.orders.update', $order) : route('admin.shipping.update', $order->shipment);
                $dom = new \DOMDocument;
                @$dom->loadHTML($response->getContent());
                $xpath = new \DOMXPath($dom);
                $this->assertSame(1, $xpath->query('//form[@action="'.$action.'"]//option[@value="'.$status.'"]')->length);
                $this->patch($action, ['status' => $status])->assertSessionHas('success');
                $this->assertSame($status, $order->fresh()->status);
                $this->assertSame($status === 'packed' ? 'preparing' : $status, $order->shipment->fresh()->status);
            }
            $this->assertSame('approved', $order->fresh()->payment_status);
            $this->assertSame('approved', $order->payment->fresh()->status);
            $this->assertNotNull($order->shipment->fresh()->shipped_at);
            $this->assertNotNull($order->shipment->fresh()->delivered_at);
            $this->get(route('receipts.show', $order))->assertOk();
        }
    }

    public function test_return_retry_delivery_recovers_cod_collection_once_and_preserves_review_history(): void
    {
        $order = $this->order('shipped');
        $order->shipment->update(['status' => 'shipped', 'shipped_at' => now()->subDay()]);
        $first = $this->admin();
        $second = $this->admin();
        $this->actingAs($first)->patch(route('admin.shipping.update', $order->shipment), ['status' => 'returned'])->assertSessionHas('success');
        $this->assertSame('rejected', $order->payment->fresh()->status);
        $this->actingAs($second)->post(route('admin.payments.approve', $order->payment))->assertSessionHas('error');
        $this->travel(1)->hours();
        $this->patch(route('admin.shipping.update', $order->shipment), ['status' => 'shipped'])->assertSessionHas('success');
        $this->patch(route('admin.shipping.update', $order->shipment), ['status' => 'delivered'])->assertSessionHas('success');
        $this->assertSame('approved', $order->payment->fresh()->status);
        $this->assertSame('approved', $order->fresh()->payment_status);
        $this->assertSame($second->id, $order->payment->fresh()->verified_by);
        $payment = $order->payment->fresh()->getRawOriginal();
        $shipment = $order->shipment->fresh()->getRawOriginal();
        $count = ActivityLog::count();
        $this->travel(1)->hours();
        $this->patch(route('admin.shipping.update', $order->shipment), ['status' => 'delivered'])->assertSessionHas('success');
        $this->assertSame($payment, $order->payment->fresh()->getRawOriginal());
        $this->assertSame($shipment, $order->shipment->fresh()->getRawOriginal());
        $this->assertSame($count, ActivityLog::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.cod_settled', 'user_id' => $first->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.cod_settled', 'user_id' => $second->id]);
        $this->get(route('receipts.show', $order))->assertOk();
    }

    public function test_legacy_delivered_cod_receipts_preserve_fulfillment_based_issuance_policy(): void
    {
        $operator = $this->admin();
        $this->actingAs($operator);
        foreach (['pending', 'rejected', 'approved'] as $paymentStatus) {
            $order = $this->order('delivered', 'cod', $paymentStatus);
            $this->get(route('receipts.show', $order))->assertOk();
            $this->actingAs($order->user)->get(route('member.orders.show', $order))
                ->assertOk()
                ->assertSee(route('receipts.show', $order), false);
            $this->actingAs($operator);
            $this->get(route('admin.orders.show', $order))->assertOk()->assertSee(route('receipts.show', $order), false);
        }
    }

    public function test_same_state_delivery_cannot_reclassify_a_legacy_rejected_cod_payment(): void
    {
        $reviewer = $this->admin();
        $order = $this->order('delivered', 'cod', 'rejected');
        $order->shipment->update([
            'status' => 'delivered',
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now()->subDay(),
        ]);
        $order->payment->update([
            'verified_by' => $reviewer->id,
            'verified_at' => now()->subDay(),
            'reject_reason' => 'Legacy failed collection',
        ]);
        $payment = $order->payment->fresh()->getRawOriginal();
        $shipment = $order->shipment->fresh()->getRawOriginal();

        $this->actingAs($this->admin())
            ->patch(route('admin.shipping.update', $order->shipment), ['status' => 'delivered'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $order->fresh()->payment_status);
        $this->assertSame($payment, $order->payment->fresh()->getRawOriginal());
        $this->assertSame($shipment, $order->shipment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_cod_delivery_rolls_back_order_shipment_and_activity_when_settlement_fails(): void
    {
        $order = $this->order('shipped');
        $order->shipment->update(['status' => 'shipped', 'shipped_at' => now()->subDay()]);
        $event = 'eloquent.updating: '.Payment::class;
        Event::listen($event, function (): void {
            throw new \RuntimeException('Settlement write failed');
        });
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->admin())->patch(route('admin.shipping.update', $order->shipment), ['status' => 'delivered']);
            $this->fail('Expected settlement failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Settlement write failed', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('shipped', $order->shipment->fresh()->status);
        $this->assertNull($order->shipment->fresh()->delivered_at);
        $this->assertSame('pending', $order->payment->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_legacy_cod_upgrade_is_bounded_idempotent_and_preserves_progressed_or_reviewed_rows(): void
    {
        $eligible = [];
        foreach (range(1, 101) as $index) {
            $eligible[] = $this->order('pending_payment');
        }
        $preserved = [];
        foreach (['paid', 'preparing', 'packed', 'shipped', 'delivery_failed', 'cancelled', 'delivered'] as $status) {
            $preserved[] = $this->order($status);
        }
        $preserved[] = $this->order('pending_payment', 'bank_transfer');
        $preserved[] = $this->order('pending_payment', 'cod', 'rejected');
        $preserved[] = $this->order('pending_payment', 'cod', 'approved');
        $reviewed = $this->order('pending_payment');
        $reviewed->payment->update(['verified_at' => now()]);
        $preserved[] = $reviewed;
        $reviewerOnly = $this->order('pending_payment');
        $reviewerOnly->payment->update(['verified_by' => $reviewerOnly->user_id]);
        $preserved[] = $reviewerOnly;
        $approvedPayment = $this->order('pending_payment');
        $approvedPayment->payment->update(['status' => 'approved']);
        $preserved[] = $approvedPayment;
        $restocked = $this->order('pending_payment');
        $restocked->update(['stock_returned_at' => now()]);
        $preserved[] = $restocked;
        $cancelled = $this->order('pending_payment');
        $cancelled->update(['cancelled_at' => now()]);
        $preserved[] = $cancelled;
        $shipped = $this->order('pending_payment');
        $shipped->shipment->update(['status' => 'shipped', 'shipped_at' => now()]);
        $preserved[] = $shipped;
        $snapshots = array_map(fn ($order) => $order->fresh()->getRawOriginal(), $preserved);
        // Execute the upgrade against existing rows, rather than only a fresh schema.
        $migration = require database_path('migrations/2026_09_25_000100_reconcile_legacy_pending_cod_orders.php');
        $migration->up();
        $migration->up();
        foreach ($eligible as $order) {
            $this->assertSame('confirmed', $order->fresh()->status);
            $this->assertNull($order->fresh()->expires_at);
        }
        foreach ($preserved as $index => $order) {
            $this->assertSame($snapshots[$index], $order->fresh()->getRawOriginal());
        }
        $this->actingAs($this->admin())->patch(route('admin.shipping.update', $eligible[0]->shipment), ['status' => 'preparing'])->assertSessionHas('success');
        $migration->down();
        $this->assertSame('preparing', $eligible[0]->fresh()->status);
        $this->assertSame('confirmed', $eligible[1]->fresh()->status);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Operator', 'email' => uniqid().'@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
    }

    private function order(string $status, string $method = 'cod', string $paymentStatus = 'pending'): Order
    {
        $order = Order::create(['user_id' => $this->admin()->id, 'order_number' => uniqid('INT-'), 'status' => $status, 'payment_status' => $paymentStatus, 'subtotal' => 100, 'shipping_fee' => 0, 'total' => 100, 'expires_at' => now()->subDay()]);
        Payment::create(['order_id' => $order->id, 'method' => $method, 'status' => $paymentStatus, 'amount' => 100]);
        Shipment::create(['order_id' => $order->id, 'status' => 'pending']);

        return $order;
    }
}
