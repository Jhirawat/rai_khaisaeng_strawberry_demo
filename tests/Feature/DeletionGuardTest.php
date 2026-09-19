<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                throw new QueryException(
                    'sqlite',
                    'delete from "categories" where "id" = ?',
                    [$category->id],
                    new \PDOException('FOREIGN KEY constraint failed'),
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
}
