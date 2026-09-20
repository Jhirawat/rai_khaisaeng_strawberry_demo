<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_updates_payment_and_order_through_the_order_workflow(): void
    {
        $admin = $this->createUser('admin');
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $response = $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.approve', $payment));

        $response->assertRedirect(route('admin.payments.index'))->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('approved', $order->fresh()->payment_status);
        $this->assertSame('approved', $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);

        $transition = ActivityLog::where('action', 'order.status_transitioned')->sole();
        $this->assertSame($admin->id, $transition->user_id);
        $this->assertSame('payment_approval', $transition->properties['source']);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment.approved',
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
        ]);
    }

    public function test_rejection_updates_payment_truth_without_advancing_the_order(): void
    {
        $admin = $this->createUser('admin');
        $order = $this->createOrder();
        $payment = $this->createPayment($order);

        $response = $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.reject', $payment), [
                'reject_reason' => 'ยอดเงินไม่ตรงกับคำสั่งซื้อ',
            ]);

        $response->assertRedirect(route('admin.payments.index'))->assertSessionHas('success');
        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame('rejected', $order->fresh()->payment_status);
        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertSame('ยอดเงินไม่ตรงกับคำสั่งซื้อ', $payment->fresh()->reject_reason);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment.rejected',
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
        ]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'order.status_transitioned']);
    }

    #[DataProvider('terminalReviewAttempts')]
    public function test_terminal_orders_cannot_be_reviewed(
        string $orderStatus,
        string $action,
    ): void {
        $admin = $this->createUser('admin');
        $order = $this->createOrder($orderStatus);
        $payment = $this->createPayment($order);
        $originalOrder = $order->fresh()->getRawOriginal();
        $originalPayment = $payment->fresh()->getRawOriginal();

        $response = $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route("admin.payments.{$action}", $payment), [
                'reject_reason' => 'must not be saved',
            ]);

        $response->assertRedirect(route('admin.payments.index'))->assertSessionHas('error');
        $this->assertSame($originalOrder, $order->fresh()->getRawOriginal());
        $this->assertSame($originalPayment, $payment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function terminalReviewAttempts(): array
    {
        return [
            'approve cancelled order' => ['cancelled', 'approve'],
            'approve delivered order' => ['delivered', 'approve'],
            'reject cancelled order' => ['cancelled', 'reject'],
            'reject delivered order' => ['delivered', 'reject'],
        ];
    }

    #[DataProvider('repeatReviewAttempts')]
    public function test_repeat_review_is_idempotent_and_writes_no_activity(
        string $paymentStatus,
        string $orderStatus,
        string $orderPaymentStatus,
        string $action,
    ): void {
        $admin = $this->createUser('admin');
        $order = $this->createOrder($orderStatus, $orderPaymentStatus);
        $payment = $this->createPayment($order, $paymentStatus);
        $payment->forceFill([
            'verified_by' => $admin->id,
            'verified_at' => now()->subHour(),
            'reject_reason' => $paymentStatus === 'rejected' ? 'original reason' : null,
        ])->save();
        $originalOrder = $order->fresh()->getRawOriginal();
        $originalPayment = $payment->fresh()->getRawOriginal();

        $response = $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route("admin.payments.{$action}", $payment), [
                'reject_reason' => 'replacement reason',
            ]);

        $response->assertRedirect(route('admin.payments.index'))->assertSessionHas('success');
        $this->assertSame($originalOrder, $order->fresh()->getRawOriginal());
        $this->assertSame($originalPayment, $payment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function repeatReviewAttempts(): array
    {
        return [
            'approved payment cannot be rejected' => ['approved', 'paid', 'approved', 'reject'],
            'rejected payment cannot be approved' => ['rejected', 'pending_payment', 'rejected', 'approve'],
        ];
    }

    public function test_payment_and_order_changes_roll_back_together_when_the_workflow_fails(): void
    {
        $admin = $this->createUser('admin');
        $order = $this->createOrder();
        $payment = $this->createPayment($order);
        $this->app->instance(OrderWorkflowService::class, new class extends OrderWorkflowService
        {
            public function transition(
                Order $order,
                string $to,
                ?User $actor = null,
                string $source = 'system',
            ): Order {
                parent::transition($order, $to, $actor, $source);

                throw new RuntimeException('Controlled payment workflow failure.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post(route('admin.payments.approve', $payment));
            $this->fail('The controlled workflow failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Controlled payment workflow failure.', $exception->getMessage());
        }

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->verified_by);
        $this->assertNull($payment->fresh()->verified_at);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_rejection_rolls_back_the_order_when_the_payment_update_fails(): void
    {
        $admin = $this->createUser('admin');
        $order = $this->createOrder();
        $payment = $this->createPayment($order);
        $eventName = 'eloquent.updating: '.Payment::class;
        Event::listen($eventName, function (Payment $updatingPayment) use ($payment): void {
            if ($updatingPayment->is($payment)) {
                throw new RuntimeException('Controlled payment update failure.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post(route('admin.payments.reject', $payment), [
                'reject_reason' => 'must roll back',
            ]);
            $this->fail('The controlled payment update failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Controlled payment update failure.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->reject_reason);
        $this->assertNull($payment->fresh()->verified_by);
        $this->assertNull($payment->fresh()->verified_at);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    #[DataProvider('allowedStaffPaymentReviews')]
    public function test_staff_can_review_a_pending_payment(
        string $action,
        string $method,
        string $expectedStatus,
    ): void {
        $staff = $this->createUser('staff');
        $payment = $this->createPayment($this->createOrder(), 'pending', $method);

        $this->actingAs($staff)
            ->from(route('admin.payments.index'))
            ->post(route("admin.payments.{$action}", $payment), [
                'reject_reason' => 'staff-reviewed rejection',
            ])
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHas('success');

        $this->assertSame($expectedStatus, $payment->fresh()->status);
    }

    public static function allowedStaffPaymentReviews(): array
    {
        return [
            'staff approves bank transfer' => ['approve', 'bank_transfer', 'approved'],
            'staff rejects QR payment' => ['reject', 'qr', 'rejected'],
        ];
    }

    #[DataProvider('paymentReviewActions')]
    public function test_members_cannot_review_payments(string $action): void
    {
        $member = $this->createUser('member');
        $payment = $this->createPayment($this->createOrder());

        $this->actingAs($member)
            ->post(route("admin.payments.{$action}", $payment), [
                'reject_reason' => 'unauthorized',
            ])
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending_payment', $payment->order->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function paymentReviewActions(): array
    {
        return [
            'approve route' => ['approve'],
            'reject route' => ['reject'],
        ];
    }

    #[DataProvider('codManualReviewAttempts')]
    public function test_cod_payments_cannot_be_manually_reviewed_without_mutation(
        string $orderStatus,
        string $action,
    ): void {
        $admin = $this->createUser('admin');
        $order = $this->createOrder($orderStatus);
        $payment = $this->createPayment($order, 'pending', 'cod');
        $originalOrder = $order->fresh()->getRawOriginal();
        $originalPayment = $payment->fresh()->getRawOriginal();

        $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route("admin.payments.{$action}", $payment), [
                'reject_reason' => 'manual COD review must be ignored',
            ])
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHas('error');

        $this->assertSame($originalOrder, $order->fresh()->getRawOriginal());
        $this->assertSame($originalPayment, $payment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public static function codManualReviewAttempts(): array
    {
        return [
            'approve COD awaiting payment' => ['pending_payment', 'approve'],
            'reject COD awaiting payment' => ['pending_payment', 'reject'],
            'approve confirmed COD' => ['confirmed', 'approve'],
            'reject confirmed COD' => ['confirmed', 'reject'],
            'approve shipped COD' => ['shipped', 'approve'],
            'reject shipped COD' => ['shipped', 'reject'],
        ];
    }

    #[DataProvider('codShippingSettlements')]
    public function test_shipping_settlement_remains_authoritative_after_cod_manual_review_is_refused(
        string $reviewAction,
        string $shipmentStatus,
        string $expectedPaymentStatus,
        string $expectedOrderStatus,
    ): void {
        $admin = $this->createUser('admin');
        $order = $this->createOrder('shipped');
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);
        $payment = $this->createPayment($order, 'pending', 'cod');

        $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route("admin.payments.{$reviewAction}", $payment), [
                'reject_reason' => 'manual COD review must be ignored',
            ])
            ->assertRedirect(route('admin.payments.index'))
            ->assertSessionHas('error');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => "payment.{$expectedPaymentStatus}",
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.shipping.update', $shipment), [
                'status' => $shipmentStatus,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($expectedOrderStatus, $order->fresh()->status);
        $this->assertSame($expectedPaymentStatus, $order->fresh()->payment_status);
        $this->assertSame($expectedPaymentStatus, $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
        $this->assertNotNull($payment->fresh()->verified_at);
    }

    public static function codShippingSettlements(): array
    {
        return [
            'delivery approves after rejected manual review' => ['reject', 'delivered', 'approved', 'delivered'],
            'return rejects after approved manual review' => ['approve', 'returned', 'rejected', 'delivery_failed'],
        ];
    }

    public function test_cod_payments_do_not_render_manual_review_controls(): void
    {
        $admin = $this->createUser('admin');
        $transferPayment = $this->createPayment($this->createOrder());
        $codPayment = $this->createPayment($this->createOrder('confirmed'), 'pending', 'cod');

        $response = $this->actingAs($admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee(route('admin.payments.approve', $transferPayment), false);
        $response->assertSee(route('admin.payments.reject', $transferPayment), false);
        $response->assertDontSee(route('admin.payments.approve', $codPayment), false);
        $response->assertDontSee(route('admin.payments.reject', $codPayment), false);
    }

    #[DataProvider('paymentReviewActions')]
    public function test_review_refuses_a_payment_whose_locked_order_association_changed(string $action): void
    {
        $admin = $this->createUser('admin');
        $routeOrder = $this->createOrder();
        $newOrder = $this->createOrder();
        $payment = $this->createPayment($routeOrder);
        $associationChanged = false;
        $eventName = 'eloquent.retrieved: '.Order::class;

        Event::listen($eventName, function (Order $retrievedOrder) use (
            &$associationChanged,
            $newOrder,
            $payment,
            $routeOrder,
        ): void {
            if (! $associationChanged && $retrievedOrder->is($routeOrder)) {
                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update(['order_id' => $newOrder->id]);
                $associationChanged = true;
            }
        });

        try {
            $response = $this->actingAs($admin)
                ->from(route('admin.payments.index'))
                ->post(route("admin.payments.{$action}", $payment), [
                    'reject_reason' => 'must not touch either order',
                ]);
        } finally {
            Event::forget($eventName);
        }

        $this->assertTrue($associationChanged);
        $response->assertRedirect(route('admin.payments.index'))->assertSessionHas('error');
        $this->assertSame('pending_payment', $routeOrder->fresh()->status);
        $this->assertSame('pending', $routeOrder->fresh()->payment_status);
        $this->assertSame('pending_payment', $newOrder->fresh()->status);
        $this->assertSame('pending', $newOrder->fresh()->payment_status);
        $this->assertSame($newOrder->id, $payment->fresh()->order_id);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->verified_by);
        $this->assertNull($payment->fresh()->verified_at);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_cod_checkout_accepts_omitted_optional_tax_fields_without_expiry_and_preserves_totals_and_stock(): void
    {
        $member = $this->createUser('member');
        $province = ThaiProvince::create(['name_th' => 'เชียงใหม่', 'name_en' => 'Chiang Mai']);
        $district = ThaiDistrict::create([
            'province_id' => $province->id,
            'name_th' => 'เมืองเชียงใหม่',
            'name_en' => 'Mueang Chiang Mai',
        ]);
        ThaiSubdistrict::create([
            'district_id' => $district->id,
            'name_th' => 'ศรีภูมิ',
            'name_en' => 'Si Phum',
            'zip_code' => '50200',
        ]);
        $category = Category::create([
            'name' => 'Fresh fruit',
            'slug' => 'fresh-fruit',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Strawberries',
            'slug' => 'strawberries',
            'price' => 150,
            'sku' => 'STRAWBERRY-TEST',
            'status' => 'active',
        ]);
        $inventory = Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'low_stock_threshold' => 2,
        ]);
        $cart = Cart::create(['user_id' => $member->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 150,
        ]);

        $response = $this->actingAs($member)->post(route('member.checkout.store'), [
            'recipient_name' => 'ลูกค้าทดสอบ',
            'phone' => '0812345678',
            'address' => '1 ถนนทดสอบ',
            'province' => 'เชียงใหม่',
            'district' => 'เมืองเชียงใหม่',
            'subdistrict' => 'ศรีภูมิ',
            'postal_code' => '50200',
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $order = Order::where('user_id', $member->id)->sole();
        $response->assertRedirect(route('member.orders.show', $order))->assertSessionHas('success');
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->expires_at);
        $this->assertSame('300.00', $order->subtotal);
        $this->assertSame('50.00', $order->shipping_fee);
        $this->assertSame('350.00', $order->total);
        $this->assertSame('19.63', $order->vat_amount);
        $this->assertSame('280.37', $order->before_vat_amount);
        $this->assertSame('0.00', $order->withholding_tax_amount);
        $this->assertSame('cod', $order->payment->method);
        $this->assertSame('pending', $order->payment->status);
        $this->assertSame('350.00', $order->payment->amount);
        $this->assertSame(8, $inventory->fresh()->quantity);
        $this->assertSame('pending', $order->shipment->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 150,
            'total' => 300,
        ]);
        $this->assertDatabaseHas('inventory_logs', [
            'inventory_id' => $inventory->id,
            'type' => 'order_deduct',
            'quantity' => 2,
            'user_id' => $member->id,
        ]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => uniqid($role.'-', true).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createOrder(
        string $status = 'pending_payment',
        string $paymentStatus = 'pending',
    ): Order {
        $customer = $this->createUser('member');

        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'PAY-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => $status,
            'payment_status' => $paymentStatus,
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
            'expires_at' => $status === 'pending_payment' ? now()->addDay() : null,
        ]);
    }

    private function createPayment(
        Order $order,
        string $status = 'pending',
        string $method = 'bank_transfer',
    ): Payment {
        return Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'amount' => $order->total,
            'slip_path' => 'slips/test-payment.jpg',
            'status' => $status,
        ]);
    }
}
