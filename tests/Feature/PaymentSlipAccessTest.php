<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use App\Models\User;
use App\Services\SlipOcrService;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PaymentSlipAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_stores_new_payment_slips_on_the_private_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $member = $this->createCheckoutCart();
        $this->app->instance(SlipOcrService::class, new class extends SlipOcrService
        {
            public function analyze(string $absolutePath, ?float $expectedAmount = null): array
            {
                return [
                    'status' => 'needs_review',
                    'score' => 0,
                    'text' => '',
                    'note' => 'Test slip requires review.',
                ];
            }
        });

        $response = $this->actingAs($member)->post(route('member.checkout.store'), [
            'recipient_name' => 'Private Slip Customer',
            'phone' => '0812345678',
            'address' => '1 Test Road',
            'province' => 'Chiang Mai',
            'district' => 'Mueang Chiang Mai',
            'subdistrict' => 'Si Phum',
            'postal_code' => '50200',
            'payment_method' => 'bank_transfer',
            'slip' => UploadedFile::fake()->createWithContent(
                'payment-slip.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
            ),
            'needs_tax_invoice' => false,
            'customer_tax_id' => null,
            'customer_tax_name' => null,
            'customer_tax_address' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirectContains('/member/orders/');
        $payment = Payment::query()->sole();
        $response->assertRedirect(route('member.orders.show', $payment->order));
        $this->assertSame('local', $payment->slip_disk);
        $this->assertStringStartsWith('payment_slips/', $payment->slip_path);
        Storage::disk('local')->assertExists($payment->slip_path);
        Storage::disk('public')->assertMissing($payment->slip_path);
    }

    #[DataProvider('operationalRoles')]
    public function test_operational_roles_can_view_a_private_slip_inline(string $role): void
    {
        Storage::fake('local');
        $contents = $this->pngBytes();
        Storage::disk('local')->put('payment_slips/private.png', $contents);
        $payment = $this->createPayment('payment_slips/private.png', 'local');

        $response = $this->actingAs($this->createUser($role))
            ->get($this->slipUrl($payment));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame($contents, $response->streamedContent());
    }

    public static function operationalRoles(): array
    {
        return [
            'staff' => ['staff'],
            'admin' => ['admin'],
            'super admin' => ['super_admin'],
        ];
    }

    public function test_members_cannot_view_payment_slips(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_slips/private.png', $this->pngBytes());
        $payment = $this->createPayment('payment_slips/private.png', 'local');

        $this->actingAs($this->createUser('member'))
            ->get($this->slipUrl($payment))
            ->assertForbidden();
    }

    public function test_missing_private_slip_returns_not_found_without_exposing_its_path(): void
    {
        Storage::fake('local');
        $payment = $this->createPayment('payment_slips/missing-sensitive-file.png', 'local');

        $response = $this->actingAs($this->createUser('staff'))
            ->get($this->slipUrl($payment));

        $response->assertNotFound();
        $response->assertDontSee('missing-sensitive-file.png');
    }

    public function test_legacy_public_slips_are_delivered_through_the_authorized_route(): void
    {
        Storage::fake('public');
        $contents = $this->pngBytes();
        Storage::disk('public')->put('slips/legacy.png', $contents);
        $payment = $this->createPayment('slips/legacy.png', 'public');

        $response = $this->actingAs($this->createUser('admin'))
            ->get($this->slipUrl($payment));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame($contents, $response->streamedContent());
    }

    #[DataProvider('unsafeSlipLocations')]
    public function test_unsafe_slip_locations_are_not_read(string $path, ?string $disk): void
    {
        Storage::fake('local');
        Storage::fake('public');
        if (in_array($disk, ['local', 'public'], true)
            && in_array($path, ['slips/private.png', 'receipts/private.png'], true)) {
            Storage::disk($disk)->put($path, $this->pngBytes());
        }
        $payment = $this->createPayment($path, $disk);

        $this->actingAs($this->createUser('admin'))
            ->get($this->slipUrl($payment))
            ->assertNotFound();
    }

    public static function unsafeSlipLocations(): array
    {
        return [
            'path traversal' => ['../private/.env', 'local'],
            'absolute path' => ['C:\\sensitive\\slip.png', 'local'],
            'unapproved disk' => ['payment_slips/private.png', 's3'],
            'missing disk metadata' => ['payment_slips/private.png', null],
            'legacy root on private disk' => ['slips/private.png', 'local'],
            'unapproved public root' => ['receipts/private.png', 'public'],
        ];
    }

    public function test_admin_payment_views_only_emit_the_authorized_slip_route(): void
    {
        $admin = $this->createUser('admin');
        $payment = $this->createPayment('slips/legacy.png', 'public');
        $protectedUrl = $this->slipUrl($payment);
        $directPublicUrl = asset('storage/slips/legacy.png');

        $index = $this->actingAs($admin)->get(route('admin.payments.index'));
        $index->assertOk();
        $index->assertSee($protectedUrl, false);
        $index->assertDontSee($directPublicUrl, false);

        $order = $this->actingAs($admin)->get(route('admin.orders.show', $payment->order));
        $order->assertOk();
        $order->assertSee($protectedUrl, false);
        $order->assertDontSee($directPublicUrl, false);
    }

    public function test_slip_migration_dry_run_reports_without_mutating_files_or_database(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $contents = $this->pngBytes();
        $payment = $this->createPayment('slips/legacy.png', 'public');
        Storage::disk('public')->put($payment->slip_path, $contents);

        $this->artisan('payments:migrate-slips-private', ['--dry-run' => true])
            ->expectsOutputToContain("Would migrate payment {$payment->id}")
            ->expectsOutput('Would migrate 1 payment slips; skipped 0; 0 failed.')
            ->assertExitCode(0);

        $this->assertSame('public', $payment->fresh()->slip_disk);
        Storage::disk('public')->assertExists($payment->slip_path);
        Storage::disk('local')->assertMissing('payment_slips/legacy.png');
    }

    public function test_slip_migration_moves_and_verifies_a_public_file_idempotently(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $contents = $this->pngBytes();
        $payment = $this->createPayment('slips/legacy.png', 'public');
        $privatePath = 'payment_slips/legacy.png';
        Storage::disk('public')->put($payment->slip_path, $contents);

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutputToContain("Migrated payment {$payment->id}")
            ->assertExitCode(0);

        $this->assertSame('local', $payment->fresh()->slip_disk);
        $this->assertSame($privatePath, $payment->fresh()->slip_path);
        Storage::disk('local')->assertExists($privatePath);
        Storage::disk('public')->assertMissing('slips/legacy.png');
        $this->assertSame($contents, Storage::disk('local')->get($privatePath));

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput('Migrated 0 payment slips; skipped 0; 0 failed.')
            ->assertExitCode(0);
        Storage::disk('local')->assertExists($privatePath);
    }

    public function test_slip_migration_skips_demo_and_non_public_records(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $demo = $this->createPayment('slips/demo-slip-qr-valid.svg', 'public');
        $alreadyPrivate = $this->createPayment('payment_slips/already-private.png', 'local');
        Storage::disk('public')->put($demo->slip_path, '<svg></svg>');
        Storage::disk('local')->put($alreadyPrivate->slip_path, $this->pngBytes());

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput('Migrated 0 payment slips; skipped 1; 0 failed.')
            ->assertExitCode(0);

        $this->assertSame('public', $demo->fresh()->slip_disk);
        $this->assertSame('local', $alreadyPrivate->fresh()->slip_disk);
        Storage::disk('public')->assertExists($demo->slip_path);
        Storage::disk('local')->assertExists($alreadyPrivate->slip_path);
    }

    public function test_slip_migration_leaves_a_missing_source_record_unchanged(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $payment = $this->createPayment('payment_slips/missing.png', 'public');

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput("Payment {$payment->id}: source file is missing.")
            ->assertExitCode(1);

        $this->assertSame('public', $payment->fresh()->slip_disk);
        Storage::disk('local')->assertMissing($payment->slip_path);
    }

    #[DataProvider('unsafeMigrationPaths')]
    public function test_slip_migration_rejects_unsafe_paths_without_touching_storage(
        string $path,
        bool $createSource,
    ): void {
        Storage::fake('public');
        Storage::fake('local');
        $payment = $this->createPayment($path, 'public');
        if ($createSource) {
            Storage::disk('public')->put($path, $this->pngBytes());
        }

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput("Payment {$payment->id}: unsafe slip path; skipped.")
            ->assertExitCode(1);

        $this->assertSame('public', $payment->fresh()->slip_disk);
        Storage::disk('local')->assertMissing('payment_slips/unsafe.png');
    }

    public static function unsafeMigrationPaths(): array
    {
        return [
            'path traversal' => ['../payment_slips/unsafe.png', false],
            'unapproved source root' => ['receipts/unsafe.png', true],
        ];
    }

    public function test_slip_migration_keeps_the_public_source_when_database_update_fails(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $payment = $this->createPayment('payment_slips/update-failure.png', 'public');
        Storage::disk('public')->put($payment->slip_path, $this->pngBytes());
        $eventName = 'eloquent.updating: '.Payment::class;
        Event::listen($eventName, function (Payment $updating) use ($payment): void {
            if ($updating->is($payment)) {
                throw new RuntimeException('Controlled slip metadata update failure.');
            }
        });

        try {
            $this->artisan('payments:migrate-slips-private')
                ->expectsOutput("Payment {$payment->id}: migration failed.")
                ->assertExitCode(1);
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame('public', $payment->fresh()->slip_disk);
        Storage::disk('public')->assertExists($payment->slip_path);
        Storage::disk('local')->assertMissing($payment->slip_path);
    }

    public function test_slip_migration_treats_a_vetoed_model_save_as_a_failure(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $payment = $this->createPayment('slips/save-veto.png', 'public');
        $privatePath = 'payment_slips/save-veto.png';
        Storage::disk('public')->put($payment->slip_path, $this->pngBytes());
        $eventName = 'eloquent.updating: '.Payment::class;
        Event::listen($eventName, function (Payment $updating) use ($payment): ?bool {
            return $updating->is($payment) ? false : null;
        });

        try {
            $this->artisan('payments:migrate-slips-private')
                ->expectsOutput("Payment {$payment->id}: migration failed.")
                ->doesntExpectOutputToContain("Migrated payment {$payment->id}:")
                ->assertExitCode(1);
        } finally {
            Event::forget($eventName);
        }

        $payment->refresh();
        $this->assertSame('public', $payment->slip_disk);
        $this->assertSame('slips/save-veto.png', $payment->slip_path);
        Storage::disk('public')->assertExists('slips/save-veto.png');
        Storage::disk('local')->assertMissing($privatePath);
    }

    public function test_slip_migration_retries_public_deletion_after_the_first_attempt_fails(): void
    {
        $public = Storage::fake('public');
        Storage::fake('local');
        $payment = $this->createPayment('slips/delete-retry.png', 'public');
        $privatePath = 'payment_slips/delete-retry.png';
        $public->put($payment->slip_path, $this->pngBytes());

        Storage::set('public', new class($public->getDriver(), $public->getAdapter(), $public->getConfig()) extends FilesystemAdapter
        {
            private bool $failNextDelete = true;

            public function delete($paths)
            {
                if ($this->failNextDelete) {
                    $this->failNextDelete = false;

                    return false;
                }

                return parent::delete($paths);
            }
        });

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput("Payment {$payment->id}: migration failed.")
            ->doesntExpectOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(1);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('public')->assertExists('slips/delete-retry.png');
        Storage::disk('local')->assertExists($privatePath);

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('public')->assertMissing('slips/delete-retry.png');
        Storage::disk('local')->assertExists($privatePath);
    }

    public function test_slip_migration_reconciles_a_committed_local_row_with_a_lingering_public_copy(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $contents = $this->pngBytes();
        $privatePath = 'payment_slips/restart-recovery.png';
        $payment = $this->createPayment($privatePath, 'local');
        Storage::disk('local')->put($privatePath, $contents);
        Storage::disk('public')->put('slips/restart-recovery.png', $contents);

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('local')->assertExists($privatePath);
        Storage::disk('public')->assertMissing('slips/restart-recovery.png');
    }

    public function test_slip_migration_skips_a_bundled_demo_copy_derived_from_a_committed_local_row(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $contents = '<svg></svg>';
        $privatePath = 'payment_slips/demo-slip-qr-valid.svg';
        $publicPath = 'slips/demo-slip-qr-valid.svg';
        $payment = $this->createPayment($privatePath, 'local');
        Storage::disk('local')->put($privatePath, $contents);
        Storage::disk('public')->put($publicPath, $contents);

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput('Migrated 0 payment slips; skipped 1; 0 failed.')
            ->doesntExpectOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('local')->assertExists($privatePath);
        Storage::disk('public')->assertExists($publicPath);
        $this->assertSame($contents, Storage::disk('public')->get($publicPath));
    }

    public function test_slip_migration_keeps_the_public_source_until_the_metadata_commit_succeeds(): void
    {
        DB::rollBack();
        Storage::fake('public');
        Storage::fake('local');
        $contents = $this->pngBytes();
        $payment = $this->createPayment('slips/commit-failure.png', 'public');
        $privatePath = 'payment_slips/commit-failure.png';
        Storage::disk('public')->put($payment->slip_path, $contents);
        $failNextCommit = true;
        Event::listen(TransactionCommitting::class, function () use (&$failNextCommit): void {
            if ($failNextCommit) {
                $failNextCommit = false;

                throw new RuntimeException('Controlled payment slip metadata commit failure.');
            }
        });

        try {
            $this->artisan('payments:migrate-slips-private')
                ->expectsOutput("Payment {$payment->id}: migration failed.")
                ->doesntExpectOutputToContain("Migrated payment {$payment->id}:")
                ->assertExitCode(1);
        } finally {
            Event::forget(TransactionCommitting::class);
            $pdo = DB::connection()->getPdo();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

        $payment->refresh();
        $this->assertSame('public', $payment->slip_disk);
        $this->assertSame('slips/commit-failure.png', $payment->slip_path);
        Storage::disk('public')->assertExists('slips/commit-failure.png');
        Storage::disk('local')->assertExists($privatePath);

        $response = $this->actingAs($this->createUser('admin'))
            ->get($this->slipUrl($payment));
        $response->assertOk();
        $this->assertSame($contents, $response->streamedContent());

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('public')->assertMissing('slips/commit-failure.png');
        Storage::disk('local')->assertExists($privatePath);
    }

    public function test_slip_migration_removes_a_partial_failed_write_so_a_rerun_can_succeed(): void
    {
        Storage::fake('public');
        $local = Storage::fake('local');
        $payment = $this->createPayment('slips/partial-write.png', 'public');
        $privatePath = 'payment_slips/partial-write.png';
        Storage::disk('public')->put($payment->slip_path, $this->pngBytes());

        Storage::set('local', new class($local->getDriver(), $local->getAdapter(), $local->getConfig()) extends FilesystemAdapter
        {
            public function put($path, $contents, $options = [])
            {
                parent::put($path, 'partial', $options);

                return false;
            }
        });

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutput("Payment {$payment->id}: migration failed.")
            ->doesntExpectOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(1);

        $payment->refresh();
        $this->assertSame('public', $payment->slip_disk);
        $this->assertSame('slips/partial-write.png', $payment->slip_path);
        Storage::disk('public')->assertExists('slips/partial-write.png');
        $local->assertMissing($privatePath);

        Storage::set('local', $local);

        $this->artisan('payments:migrate-slips-private')
            ->expectsOutputToContain("Migrated payment {$payment->id}:")
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame('local', $payment->slip_disk);
        $this->assertSame($privatePath, $payment->slip_path);
        Storage::disk('public')->assertMissing('slips/partial-write.png');
        $local->assertExists($privatePath);
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
        $category = Category::create([
            'name' => 'Fresh fruit',
            'slug' => 'private-slip-fruit',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Private Slip Strawberries',
            'slug' => 'private-slip-strawberries',
            'price' => 150,
            'sku' => 'PRIVATE-SLIP-TEST',
            'status' => 'active',
        ]);
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
            'price' => 150,
        ]);

        return $member;
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

    private function createPayment(string $slipPath, ?string $slipDisk): Payment
    {
        $customer = $this->createUser('member');
        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'SLIP-'.strtoupper(bin2hex(random_bytes(5))),
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'subtotal' => 300,
            'shipping_fee' => 50,
            'total' => 350,
            'ordered_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'method' => 'bank_transfer',
            'amount' => 350,
            'slip_path' => $slipPath,
            'slip_disk' => $slipDisk,
            'status' => 'pending',
        ]);
    }

    private function slipUrl(Payment $payment): string
    {
        return "/admin/payments/{$payment->getKey()}/slip";
    }

    private function pngBytes(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
