# Urgent Commerce Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make local order, payment, inventory, payment-slip, and deletion workflows safe, consistent, and covered by automated tests.

**Architecture:** Add one `OrderWorkflowService` as the authority for order transitions and stock restoration. Keep controllers thin, store payment slips on a private disk with authorized delivery, and use application guards to preserve transaction history. Run tests with SQLite in memory so development MySQL data is never touched.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent/MySQL, SQLite in-memory tests, PHPUnit 11, Blade, XAMPP, Composer.

**Spec:** `docs/superpowers/specs/2026-09-11-urgent-commerce-hardening-design.md`

## Global Constraints

- Operate locally through VS Code, XAMPP, Composer, and MySQL; Railway is out of scope.
- Preserve existing storefront, checkout totals, receipts, admin pages, bilingual behavior, and current user roles.
- Never use the development database for automated tests.
- Never commit `.env`, credentials, API keys, real payment slips, or customer data.
- Preserve the existing uncommitted UI/backoffice improvements in a separate commit before hardening changes.

## File Map

- `app/Services/OrderWorkflowService.php`: transition rules, row locking, stock restoration, and inventory history.
- `app/Exceptions/InvalidOrderTransition.php`: domain error with safe user-facing text.
- `app/Models/Order.php`: labels and presentation for the new `confirmed` state.
- `app/Http/Controllers/Admin/OrderController.php`: delegates manual and bulk changes to the workflow service.
- `app/Http/Controllers/Admin/PaymentController.php`: atomic payment review and authorized slip delivery.
- `app/Http/Controllers/Admin/ShippingController.php`: maps shipment updates through the workflow service.
- `app/Http/Controllers/Admin/UserController.php`: blocks deletion when orders exist.
- `app/Http/Controllers/Admin/CategoryController.php`: blocks deletion when products exist.
- `app/Http/Controllers/Member/CheckoutController.php`: writes new slips to private storage.
- `database/migrations/2026_01_01_000009_create_orders_table.php`: keeps fresh SQLite test schema aligned with the final order states.
- `database/migrations/2026_06_08_000020_make_thai_subdistrict_zip_code_nullable.php`: skips MySQL-only SQL during SQLite tests.
- `database/migrations/2026_06_08_000030_extend_order_statuses.php`: skips MySQL-only SQL during SQLite tests.
- `database/migrations/2026_09_11_000050_add_confirmed_order_status.php`: adds the COD-safe state to existing MySQL databases.
- `database/migrations/2026_09_11_000100_add_slip_disk_to_payments_table.php`: distinguishes legacy public and new private slips.
- `routes/console.php`: safe expiry command, scheduler registration, and legacy slip migration command.
- `routes/web.php`: protected slip preview route.
- `resources/views/admin/payments/index.blade.php`: uses protected slip route.
- `resources/views/admin/orders/show.blade.php`: uses protected slip route and valid transition choices.
- `resources/views/admin/orders/index.blade.php`: limits bulk status choices to valid targets.
- `resources/views/auth/login.blade.php`: local-only demo credentials.
- `tests/TestCase.php`, `phpunit.xml`: isolated Laravel test foundation.
- `tests/Unit/OrderWorkflowServiceTest.php`: transition matrix tests.
- `tests/Feature/OrderExpiryTest.php`: expiry and stock idempotency.
- `tests/Feature/PaymentWorkflowTest.php`: atomic approval/rejection rules.
- `tests/Feature/PaymentSlipAccessTest.php`: private storage and authorization.
- `tests/Feature/DeletionGuardTest.php`: customer/category history preservation.
- `tests/Feature/DemoCredentialsVisibilityTest.php`: environment-specific login content.
- `README_TH_VSCODE.md`, `PRODUCTION_CHECKLIST_TH.md`: local scheduler, testing, and slip migration instructions.

---

### Task 1: Preserve Existing Work and Add an Isolated Test Harness

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml`
- Create: `tests/TestCase.php`
- Create: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Consumes: XAMPP PHP executable and current Laravel application bootstrap.
- Produces: `php artisan test` support using `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.

- [ ] **Step 1: Verify and commit the existing UI/backoffice work separately**

Run:

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip vendor\bin\pint --test
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan view:cache
git diff --check
git add CHANGELOG.md app public resources routes/web.php
git commit -m "feat: professionalize backoffice workflows and receipts"
```

Expected: checks pass; the hardening work starts from a clean tree containing the prior approved changes.

- [ ] **Step 2: Add Laravel test dependencies**

Add to `require-dev` in `composer.json`:

```json
"mockery/mockery": "^1.6",
"nunomaduro/collision": "^8.6",
"phpunit/phpunit": "^11.5"
```

Run:

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip ..\..\work\composer.phar update mockery/mockery nunomaduro/collision phpunit/phpunit --with-all-dependencies
```

Expected: `composer.lock` contains PHPUnit 11 and the command exits successfully.

- [ ] **Step 3: Create the isolated test configuration**

Create `phpunit.xml` with the Laravel bootstrap and these environment overrides:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing" force="true"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite" force="true"/>
        <env name="DB_DATABASE" value=":memory:" force="true"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

Create `tests/TestCase.php`:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
}
```

Make fresh-test migrations portable: include all final order states in `2026_01_01_000009_create_orders_table.php`, and return early from the two later raw `ALTER TABLE ... MODIFY` migrations when `DB::getDriverName() !== 'mysql'`. Production behavior remains unchanged because those statements still run on MySQL.

- [ ] **Step 4: Write and run a smoke test**

Create `tests/Feature/SmokeTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_available(): void
    {
        $this->get('/')->assertOk();
    }
}
```

Run:

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd -d extension=zip artisan test tests\Feature\SmokeTest.php
```

Expected: one passing test and no connection to `maeyangha_shop_codex`.

- [ ] **Step 5: Commit the test foundation**

```powershell
git add composer.json composer.lock phpunit.xml tests
git commit -m "test: add isolated Laravel test foundation"
```

---

### Task 2: Centralize Order Transitions and Stock Restoration

**Files:**
- Create: `app/Exceptions/InvalidOrderTransition.php`
- Create: `app/Services/OrderWorkflowService.php`
- Create: `tests/Unit/OrderWorkflowServiceTest.php`
- Modify: `app/Http/Controllers/Admin/OrderController.php`
- Modify: `resources/views/admin/orders/show.blade.php`
- Modify: `resources/views/admin/orders/index.blade.php`

**Interfaces:**
- Produces: `OrderWorkflowService::allowedTargets(string $from): array`.
- Produces: `OrderWorkflowService::transition(Order $order, string $to, ?User $actor = null, string $source = 'system'): Order`.
- Throws: `InvalidOrderTransition` for disallowed transitions.

- [ ] **Step 1: Write failing transition-matrix tests**

Cover this exact map:

```php
[
    'pending_payment' => ['paid', 'cancelled'],
    'confirmed' => ['preparing', 'cancelled'],
    'paid' => ['preparing', 'cancelled'],
    'preparing' => ['packed', 'cancelled'],
    'packed' => ['shipped', 'cancelled'],
    'shipped' => ['delivered', 'delivery_failed'],
    'delivery_failed' => ['shipped', 'cancelled'],
    'delivered' => [],
    'cancelled' => [],
]
```

Tests assert every listed target is accepted and representative backward/skipped targets throw `InvalidOrderTransition` without changing the database row.

- [ ] **Step 2: Run tests and confirm the service is missing**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Unit\OrderWorkflowServiceTest.php
```

Expected: FAIL because `OrderWorkflowService` does not exist.

- [ ] **Step 3: Add the domain exception and workflow service**

`InvalidOrderTransition` accepts `$from` and `$to` and formats a Thai-safe message without internal SQL details.

The service must:

```php
public function allowedTargets(string $from): array;
public function transition(Order $order, string $to, ?User $actor = null, string $source = 'system'): Order;
```

Inside `transition`, use `DB::transaction`, reload the order with `lockForUpdate()`, reject targets outside the map, and return early when `$from === $to`. When entering `cancelled`, lock each inventory row, increment once, create `InventoryLog` with type `add`, and set `stock_returned_at`. Set `cancelled_at` only when entering `cancelled`.

Add `2026_09_11_000050_add_confirmed_order_status.php`. On MySQL its `up()` expands the enum to `pending_payment,confirmed,paid,preparing,packed,shipped,delivered,delivery_failed,cancelled`; its `down()` converts `confirmed` rows to `pending_payment` before restoring the previous enum. SQLite fresh tests receive the same list from the corrected create migration.

Update `Order::statusLabels()`, badge classes, filters, summaries, and admin views for `confirmed` with Thai text `ยืนยันแล้ว` and English text `Confirmed`.

- [ ] **Step 4: Run the transition tests**

Expected: PASS for the full matrix and the idempotent stock-return case.

- [ ] **Step 5: Replace controller-local transition logic**

Inject `OrderWorkflowService` into `Admin\OrderController`. Both `update` and `bulkUpdate` call `transition`; catch `InvalidOrderTransition` and return a clear error. Remove the controller's private `returnStockOnce` method.

For order views, build each row's selectable status list from `allowedTargets($order->status)` plus the current state. Terminal rows remain disabled.

- [ ] **Step 6: Run focused and full tests, then commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Unit\OrderWorkflowServiceTest.php
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test
git add app/Exceptions app/Services app/Models/Order.php app/Http/Controllers/Admin/OrderController.php database/migrations resources/views/admin/orders tests/Unit
git commit -m "feat: enforce safe order state transitions"
```

---

### Task 3: Make Expiry and Shipping Use the Workflow

**Files:**
- Modify: `routes/console.php`
- Modify: `app/Http/Controllers/Admin/ShippingController.php`
- Create: `tests/Feature/OrderExpiryTest.php`
- Create: `tests/Feature/ShippingWorkflowTest.php`

**Interfaces:**
- Consumes: `OrderWorkflowService::transition(...)` from Task 2.
- Produces: scheduled `orders:expire` command that returns stock exactly once.

- [ ] **Step 1: Write failing expiry tests**

Create an expired transfer `pending_payment` order with one item and inventory already deducted. Run:

```php
$this->artisan('orders:expire')->assertSuccessful();
```

Assert status is `cancelled`, inventory increases by the item quantity, `stock_returned_at` is set, and one inventory log is created. Run the command again and assert quantity/log count do not change.

Create an old COD `confirmed` order with no expiry and assert the command does not cancel it or restore its stock.

- [ ] **Step 2: Write failing shipping tests**

Assert shipment `shipped` transitions only a `packed` order to `shipped`; attempting it from `pending_payment`, `cancelled`, or `delivered` leaves both records unchanged and returns an error.

- [ ] **Step 3: Run both tests and verify the current behavior fails**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\OrderExpiryTest.php tests\Feature\ShippingWorkflowTest.php
```

- [ ] **Step 4: Implement safe expiry and scheduler registration**

In `routes/console.php`, process expired order IDs with `chunkById(100)` and call the workflow service for each. Track successes and failures separately. Register:

```php
Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
```

Import `Illuminate\Support\Facades\Schedule`. The command remains manually runnable.

- [ ] **Step 5: Route shipment status through the service**

Map shipment statuses to order targets:

```php
[
    'preparing' => 'preparing',
    'shipped' => 'shipped',
    'delivered' => 'delivered',
    'returned' => 'delivery_failed',
]
```

Update order and shipment inside one transaction. Reject invalid transitions before saving shipment timestamps.

For COD, a delivered shipment marks the related pending payment approved and records the reviewing admin and time; a returned shipment marks it rejected. Test both outcomes.

- [ ] **Step 6: Run tests and commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\OrderExpiryTest.php tests\Feature\ShippingWorkflowTest.php
git add routes/console.php app/Http/Controllers/Admin/ShippingController.php tests/Feature
git commit -m "fix: expire orders and restore stock safely"
```

---

### Task 4: Make Payment Review Atomic

**Files:**
- Modify: `app/Http/Controllers/Admin/PaymentController.php`
- Create: `tests/Feature/PaymentWorkflowTest.php`

**Interfaces:**
- Consumes: `OrderWorkflowService::transition(...)`.
- Produces: atomic approve/reject behavior for a pending `Payment`.

- [ ] **Step 1: Write failing payment workflow tests**

Test these cases:

- approving pending payment on `pending_payment` sets payment `approved`, order payment status `approved`, and order status `paid`
- rejecting pending payment sets payment/order payment status `rejected` but retains order `pending_payment`
- approval of a cancelled/delivered order fails without modifying payment
- a second review of approved/rejected payment makes no changes
- staff/admin routes remain allowed while members receive 403
- COD checkout creates status `confirmed`, keeps payment pending, and sets `expires_at` to null

- [ ] **Step 2: Run tests and capture the current invalid cases**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\PaymentWorkflowTest.php
```

- [ ] **Step 3: Implement transactional payment review**

Inject the workflow service. In both actions use `DB::transaction`, reload payment and order with `lockForUpdate()`, check payment is pending, check the order is not terminal, and only then update. Approval calls `transition($order, 'paid', auth()->user(), 'payment_approval')`. Activity logging occurs after a successful transaction.

- [ ] **Step 4: Run tests and commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\PaymentWorkflowTest.php
git add app/Http/Controllers/Admin/PaymentController.php tests/Feature/PaymentWorkflowTest.php
git commit -m "fix: keep payment and order state atomic"
```

---

### Task 5: Protect Payment Slips With Private Storage

**Files:**
- Create: `database/migrations/2026_09_11_000100_add_slip_disk_to_payments_table.php`
- Modify: `app/Models/Payment.php`
- Modify: `app/Http/Controllers/Member/CheckoutController.php`
- Modify: `app/Http/Controllers/Admin/PaymentController.php`
- Modify: `routes/web.php`
- Modify: `routes/console.php`
- Modify: `resources/views/admin/payments/index.blade.php`
- Modify: `resources/views/admin/orders/show.blade.php`
- Create: `tests/Feature/PaymentSlipAccessTest.php`

**Interfaces:**
- Produces: nullable `payments.slip_disk`, with migration default `public` for legacy rows.
- Produces: route `admin.payments.slip` accepting only a bound `Payment` model.
- Produces: `payments:migrate-slips-private {--dry-run}`.

- [ ] **Step 1: Write failing private-slip tests**

Use `Storage::fake('local')` and `Storage::fake('public')`. Assert checkout writes a new slip under local `payment_slips/` with `slip_disk=local`; members receive 403 from the slip route; staff/admin receive an image response; missing files return 404; legacy public records are delivered only through the authorized route.

- [ ] **Step 2: Run the tests and confirm public exposure behavior**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd artisan test tests\Feature\PaymentSlipAccessTest.php
```

- [ ] **Step 3: Add slip disk metadata and private writes**

The migration adds:

```php
$table->string('slip_disk', 20)->nullable()->default('public')->after('slip_path');
```

Add `slip_disk` to `Payment::$fillable`. Checkout stores new uploads with:

```php
$path = $request->file('slip')->store('payment_slips', 'local');
```

and persists `slip_disk => 'local'`.

- [ ] **Step 4: Add authorized slip delivery**

Add `PaymentController::slip(Payment $payment)` inside the existing staff/admin/super-admin route group. Resolve only the disk/path stored on the payment, verify existence, detect MIME through the storage driver, and return an inline response with `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`.

Templates use `route('admin.payments.slip', $payment)` and never `asset('storage/'.$payment->slip_path)`.

- [ ] **Step 5: Add legacy migration command**

`payments:migrate-slips-private --dry-run` selects non-demo rows with `slip_disk=public`, copies the file to local private storage, verifies the destination exists, updates the row, then deletes the public original. Dry-run prints intended moves without mutation. Paths beginning with `slips/demo-slip-` are skipped.

- [ ] **Step 6: Run migration/tests and commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\PaymentSlipAccessTest.php
C:\xampp\php\php.exe artisan migrate --pretend
git add app database/migrations routes resources/views/admin tests/Feature/PaymentSlipAccessTest.php
git commit -m "fix: protect payment slips behind authorization"
```

---

### Task 6: Preserve Customer and Category History

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `app/Http/Controllers/Admin/CategoryController.php`
- Modify: `resources/views/admin/users/show.blade.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/admin/categories/index.blade.php`
- Create: `tests/Feature/DeletionGuardTest.php`

**Interfaces:**
- Produces: application-level deletion guards using `orders()->exists()` and `products()->exists()`.

- [ ] **Step 1: Write failing deletion tests**

Assert a super admin cannot delete a user with orders or a category with products. Assert the records and related orders/products remain. Also assert an unused member and empty category can still be deleted.

- [ ] **Step 2: Run tests and verify the current cascade risk**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\DeletionGuardTest.php
```

- [ ] **Step 3: Add controller guards and UI states**

Before deletion:

```php
if ($user->orders()->exists()) {
    return back()->with('error', 'ไม่สามารถลบสมาชิกที่มีประวัติคำสั่งซื้อได้ กรุณาระงับบัญชีแทน');
}
```

and:

```php
if ($category->products()->exists()) {
    return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่ยังมีสินค้าได้ กรุณาย้ายสินค้าหรือปิดใช้งานหมวดหมู่แทน');
}
```

Disable or hide delete buttons when the relevant relationship count is non-zero and show the safe alternative.

- [ ] **Step 4: Run tests and commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\DeletionGuardTest.php
git add app/Http/Controllers/Admin resources/views/admin tests/Feature/DeletionGuardTest.php
git commit -m "fix: preserve transaction history on deletion"
```

---

### Task 7: Hide Demo Credentials and Document Local Operations

**Files:**
- Modify: `resources/views/auth/login.blade.php`
- Modify: `README_TH_VSCODE.md`
- Modify: `PRODUCTION_CHECKLIST_TH.md`
- Create: `tests/Feature/DemoCredentialsVisibilityTest.php`

**Interfaces:**
- Produces: demo credential block visible only in the local environment.
- Produces: local scheduler/test/private-slip operating instructions.

- [ ] **Step 1: Write failing environment-visibility tests**

Render the login page under `local` and assert `user_test@khaisaeng.test` is visible. Render under `production` and assert all three demo emails and the literal demo password are absent.

- [ ] **Step 2: Implement local-only Blade rendering**

Wrap the demo account block with:

```blade
@env('local')
    {{-- existing demo account details --}}
@endenv
```

- [ ] **Step 3: Update local documentation**

Document exact commands:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
C:\xampp\php\php.exe artisan schedule:work
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd -d extension=zip artisan test
C:\xampp\php\php.exe artisan payments:migrate-slips-private --dry-run
C:\xampp\php\php.exe artisan payments:migrate-slips-private
```

Explain that XAMPP MySQL must be running for normal use, while tests use temporary in-memory SQLite.

- [ ] **Step 4: Run tests and commit**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 artisan test tests\Feature\DemoCredentialsVisibilityTest.php
git add resources/views/auth/login.blade.php README_TH_VSCODE.md PRODUCTION_CHECKLIST_TH.md tests/Feature/DemoCredentialsVisibilityTest.php
git commit -m "docs: secure and explain local store operations"
```

---

### Task 8: Full Regression and Local Smoke Verification

**Files:**
- Modify if needed: only files implicated by failing checks.
- Modify: `CHANGELOG.md`

**Interfaces:**
- Consumes: all prior tasks.
- Produces: verified hardening release on the existing feature branch.

- [ ] **Step 1: Run format and static repository checks**

```powershell
C:\xampp\php\php.exe -d extension=gd -d extension=zip vendor\bin\pint --test
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan optimize:clear
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan view:cache
C:\xampp\php\php.exe -d extension=gd -d extension=zip artisan route:list --except-vendor
git diff --check
```

Expected: all commands exit zero and protected slip routes are present.

- [ ] **Step 2: Run the entire automated suite**

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd -d extension=zip artisan test
```

Expected: all tests pass against `:memory:`.

- [ ] **Step 3: Run local MySQL smoke checks**

With XAMPP MySQL running, verify homepage, product page, login, cart, checkout, member orders, admin orders, payment review, inventory, receipt HTML, and receipt PDF return successful responses using demo data only.

- [ ] **Step 4: Verify safety invariants manually**

Run `orders:expire` twice on a dedicated expired demo order and confirm stock changes once. Confirm a copied demo payment slip is reachable through the authorized admin route and not through a newly generated `/storage/payment_slips/...` URL. Confirm deleting a demo customer with orders is blocked.

- [ ] **Step 5: Update changelog and commit final verification notes**

Record the lifecycle, expiry, private-slip, deletion-guard, demo-visibility, and automated-test changes in `CHANGELOG.md`.

```powershell
git add CHANGELOG.md
git commit -m "chore: document urgent commerce hardening"
git status -sb
git log --oneline -10
```

Expected: clean working tree, focused commits, and no `.env` or uploaded real customer files staged.
