<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class DeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_cannot_delete_a_user_with_orders(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $customer = $this->createUser('member');
        $order = $this->createOrder($customer);

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer));

        $response->assertRedirect()
            ->assertSessionHas('error', 'ไม่สามารถลบสมาชิกที่มีประวัติคำสั่งซื้อได้ กรุณาระงับบัญชีแทน');
        $this->assertNotSoftDeleted($customer);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $customer->id,
        ]);
        $this->assertTrue($customer->orders()->whereKey($order->id)->exists());
    }

    public function test_super_admin_cannot_delete_a_category_with_products(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $product = $this->createProduct($category);

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect()
            ->assertSessionHas('error', 'ไม่สามารถลบหมวดหมู่ที่ยังมีสินค้าได้ กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'deleted_at' => null,
        ]);
        $this->assertTrue($category->products()->whereKey($product->id)->exists());
    }

    public function test_super_admin_cannot_delete_a_category_with_a_soft_deleted_product(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $product = $this->createProduct($category);
        $product->delete();
        $deletedAt = $product->fresh()->deleted_at;

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect()
            ->assertSessionHas('error', 'ไม่สามารถลบหมวดหมู่ที่ยังมีสินค้าได้ กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'deleted_at' => $deletedAt,
        ]);
        $this->assertTrue(
            $category->products()->withTrashed()->whereKey($product->id)->exists(),
        );
    }

    public function test_super_admin_can_delete_an_unused_member(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $member = $this->createUser('member');

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $member));

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');
        $this->assertSoftDeleted($member);
    }

    public function test_super_admin_can_delete_an_empty_category(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_database_refusal_to_delete_a_category_returns_safe_feedback_and_preserves_it(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $failDelete = true;

        Category::deleting(function (Category $deleting) use ($category, &$failDelete): void {
            if ($failDelete && $deleting->is($category)) {
                $previous = new PDOException('FOREIGN KEY constraint failed');
                $previous->errorInfo = ['23000', 19, 'FOREIGN KEY constraint failed'];

                throw new QueryException(
                    'sqlite',
                    'delete from "categories" where "id" = ?',
                    [$category->id],
                    $previous,
                );
            }
        });

        try {
            $response = $this->actingAs($superAdmin)
                ->delete(route('admin.categories.destroy', $category));
        } finally {
            $failDelete = false;
        }

        $response->assertRedirect()
            ->assertSessionHas('error', 'ไม่สามารถลบหมวดหมู่ได้ เนื่องจากมีข้อมูลที่เกี่ยวข้อง กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unrelated_query_exception_during_category_deletion_is_rethrown(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $failDelete = true;
        $previous = new PDOException('database disk image is malformed');
        $previous->errorInfo = ['HY000', 11, 'database disk image is malformed'];
        $expected = new QueryException(
            'sqlite',
            'delete from "categories" where "id" = ?',
            [$category->id],
            $previous,
        );

        Category::deleting(function (Category $deleting) use ($category, &$failDelete, $expected): void {
            if ($failDelete && $deleting->is($category)) {
                throw $expected;
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($superAdmin)
                ->delete(route('admin.categories.destroy', $category));
            $this->fail('The unrelated query exception was swallowed.');
        } catch (QueryException $actual) {
            $this->assertSame($expected, $actual);
        } finally {
            $failDelete = false;
            $this->withExceptionHandling();
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_unrelated_query_exception_during_user_deletion_is_rethrown(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $member = $this->createUser('member');
        $failDelete = true;
        $previous = new PDOException('database disk image is malformed');
        $previous->errorInfo = ['HY000', 11, 'database disk image is malformed'];
        $expected = new QueryException(
            'sqlite',
            'update "users" set "deleted_at" = ? where "id" = ?',
            [now(), $member->id],
            $previous,
        );

        User::deleting(function (User $deleting) use ($member, &$failDelete, $expected): void {
            if ($failDelete && $deleting->is($member)) {
                throw $expected;
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($superAdmin)
                ->delete(route('admin.users.destroy', $member));
            $this->fail('The unrelated query exception was swallowed.');
        } catch (QueryException $actual) {
            $this->assertSame($expected, $actual);
        } finally {
            $failDelete = false;
            $this->withExceptionHandling();
        }

        $this->assertNotSoftDeleted($member);
    }

    public function test_user_pages_hide_protected_delete_control_and_recommend_suspension(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $customer = $this->createUser('member');
        $unusedMember = $this->createUser('member');
        $this->createOrder($customer);
        $protectedDeleteControl = 'aria-label="ลบบัญชี '.$customer->name.'"';
        $safeDeleteControl = 'aria-label="ลบบัญชี '.$unusedMember->name.'"';

        $show = $this->actingAs($superAdmin)->get(route('admin.users.show', $customer));
        $unusedShow = $this->actingAs($superAdmin)->get(route('admin.users.show', $unusedMember));
        $index = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $show->assertOk()
            ->assertDontSee($protectedDeleteControl, false)
            ->assertSee('ไม่สามารถลบบัญชีที่มีประวัติคำสั่งซื้อได้')
            ->assertSee('กรุณาระงับบัญชีแทน');
        $unusedShow->assertOk()
            ->assertSee($safeDeleteControl, false);
        $index->assertOk()
            ->assertSee('มีประวัติคำสั่งซื้อ')
            ->assertSee('ระงับบัญชีแทน');
    }

    public function test_category_page_hides_protected_delete_control_and_recommends_a_safe_alternative(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $emptyCategory = $this->createCategory();
        $this->createProduct($category);
        $protectedDeleteControl = 'aria-label="ลบหมวดหมู่ '.$category->name.'"';
        $safeDeleteControl = 'aria-label="ลบหมวดหมู่ '.$emptyCategory->name.'"';

        $response = $this->actingAs($superAdmin)->get(route('admin.categories.index'));

        $response->assertOk()
            ->assertDontSee($protectedDeleteControl, false)
            ->assertSee($safeDeleteControl, false)
            ->assertSee('ย้ายสินค้าหรือปิดใช้งานหมวดหมู่ก่อน');
    }

    public function test_category_page_counts_soft_deleted_products_and_hides_delete_control(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $category = $this->createCategory();
        $product = $this->createProduct($category);
        $product->delete();
        $deleteControl = 'aria-label="ลบหมวดหมู่ '.$category->name.'"';

        $response = $this->actingAs($superAdmin)->get(route('admin.categories.index'));

        $response->assertOk()
            ->assertSee('1 รายการ')
            ->assertDontSee($deleteControl, false)
            ->assertSee('ย้ายสินค้าหรือปิดใช้งานหมวดหมู่ก่อน');
    }

    public function test_checkout_refuses_when_the_authenticated_user_was_soft_deleted_before_order_creation(): void
    {
        $member = $this->createCheckoutCart();
        $this->actingAs($member);
        $member->delete();

        $response = $this->post(route('member.checkout.store'), $this->checkoutPayload());

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'ไม่สามารถสร้างคำสั่งซื้อได้ เนื่องจากบัญชีไม่ได้เปิดใช้งาน');
        $this->assertDatabaseMissing('orders', ['user_id' => $member->id]);
    }

    public function test_checkout_refuses_when_the_authenticated_user_was_suspended_before_order_creation(): void
    {
        $member = $this->createCheckoutCart();
        $this->actingAs($member);
        User::query()->whereKey($member->id)->update(['is_active' => false]);

        $response = $this->post(route('member.checkout.store'), $this->checkoutPayload());

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'ไม่สามารถสร้างคำสั่งซื้อได้ เนื่องจากบัญชีไม่ได้เปิดใช้งาน');
        $this->assertDatabaseMissing('orders', ['user_id' => $member->id]);
    }

    public function test_checkout_completed_before_deletion_preserves_the_user_and_order(): void
    {
        $member = $this->createCheckoutCart();

        $checkout = $this->actingAs($member)
            ->post(route('member.checkout.store'), $this->checkoutPayload());
        $order = Order::query()->where('user_id', $member->id)->sole();

        $checkout->assertRedirect(route('member.orders.show', $order));

        $deletion = $this->actingAs($this->createUser('super_admin'))
            ->delete(route('admin.users.destroy', $member));

        $deletion->assertRedirect()
            ->assertSessionHas('error', 'ไม่สามารถลบสมาชิกที่มีประวัติคำสั่งซื้อได้ กรุณาระงับบัญชีแทน');
        $this->assertNotSoftDeleted($member);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_user_show_provides_only_the_latest_ten_orders_with_loaded_item_products(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $customer = $this->createUser('member');
        $category = $this->createCategory();
        $product = $this->createProduct($category);
        $orders = collect();

        foreach (range(1, 12) as $position) {
            $order = $this->createOrder($customer);
            $order->update(['created_at' => now()->addMinutes($position)]);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => 1,
                'price' => $product->price,
                'total' => $product->price,
            ]);
            $orders->push($order);
        }

        $response = $this->actingAs($superAdmin)->get(route('admin.users.show', $customer));

        $response->assertOk();
        $viewData = $response->viewData();
        $this->assertArrayHasKey(
            'latestOrders',
            $viewData,
            'The view must receive a bounded latest-orders collection.',
        );
        $latestOrders = $viewData['latestOrders'];
        $this->assertCount(10, $latestOrders);
        $this->assertSame(
            $orders->sortByDesc('created_at')->take(10)->pluck('id')->values()->all(),
            $latestOrders->pluck('id')->all(),
        );
        $this->assertFalse($viewData['user']->relationLoaded('orders'));
        $this->assertTrue($latestOrders->every(
            fn (Order $order): bool => $order->relationLoaded('items')
                && $order->items->every(fn (OrderItem $item): bool => $item->relationLoaded('product')),
        ));
    }

    public function test_super_admin_still_cannot_delete_their_own_account(): void
    {
        $superAdmin = $this->createUser('super_admin');

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $superAdmin));

        $response->assertRedirect()
            ->assertSessionHas('success', 'ไม่สามารถลบบัญชีของตนเองได้');
        $this->assertNotSoftDeleted($superAdmin);
    }

    public function test_non_super_admin_still_cannot_delete_users(): void
    {
        $admin = $this->createUser('admin');
        $member = $this->createUser('member');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $member))
            ->assertForbidden();

        $this->assertNotSoftDeleted($member);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => uniqid($role.'-', true).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createOrder(User $customer): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'order_number' => 'DELETE-GUARD-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
        ]);
    }

    private function createCategory(): Category
    {
        $suffix = strtolower(bin2hex(random_bytes(5)));

        return Category::create([
            'name' => 'Deletion Guard '.$suffix,
            'slug' => 'deletion-guard-'.$suffix,
            'is_active' => true,
        ]);
    }

    private function createProduct(Category $category): Product
    {
        $suffix = strtoupper(bin2hex(random_bytes(5)));

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Protected Product '.$suffix,
            'slug' => 'protected-product-'.strtolower($suffix),
            'price' => 100,
            'cost' => 50,
            'sku' => 'GUARD-'.$suffix,
            'status' => 'active',
        ]);
    }

    private function createCheckoutCart(): User
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
        $product = $this->createProduct($this->createCategory());
        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'low_stock_threshold' => 2,
        ]);
        $cart = Cart::create(['user_id' => $member->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        return $member;
    }

    private function checkoutPayload(): array
    {
        return [
            'recipient_name' => 'ลูกค้าทดสอบ',
            'phone' => '0812345678',
            'address' => '1 ถนนทดสอบ',
            'province' => 'เชียงใหม่',
            'district' => 'เมืองเชียงใหม่',
            'subdistrict' => 'ศรีภูมิ',
            'postal_code' => '50200',
            'payment_method' => 'cod',
            'needs_tax_invoice' => false,
            'customer_tax_id' => null,
            'customer_tax_name' => null,
            'customer_tax_address' => null,
        ];
    }
}
