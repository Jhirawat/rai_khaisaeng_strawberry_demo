# Final integration fix report — urgent commerce hardening

Date: 2026-09-25

Branch: `codex/urgent-commerce-hardening`

Reviewed starting point: `a514e79` plus an interrupted, uncommitted integration patch.

Implementation commit: `7a246db fix: close commerce workflow integration gaps`

Report commit: `docs: record final commerce integration fix` (this report)

## Outcome

All five findings in `final-branch-review.md` are addressed. The final application boundary now owns payment approval, shipment transitions, COD settlement, expiry, and stock return consistently. Operator controls expose only the safe operation for each lifecycle step. A bounded, idempotent legacy COD reconciliation is included, and the existing fulfillment-based receipt policy is explicit and centralized.

No development or retained MySQL database was accessed. All executable database validation used guarded SQLite only; MySQL compatibility was checked by compiling the migration queries with Laravel's MySQL grammar and reviewing the generated locking/update SQL.

## Partial-work audit

The inherited worktree contained modified workflow/controllers/views/tests plus a new migration, integration test, and operator document. I audited the entire status, tracked diff, and contents of all untracked files before editing.

Valid inherited work retained:

- Rejected transfer/QR expiry predicates were widened in both command selection and locked service revalidation.
- Generic paid and shipment-owned transitions were blocked at the shared service boundary, while payment approval pre-staged both approved records inside its outer transaction before requesting the paid transition.
- Shipment updates were moved into the workflow service with one order/shipment/payment transaction, timestamps, COD settlement, activity, and order-first locking.
- The order detail page gained a usable shipping form; the payment page links COD operators to it; generic list/detail controls were filtered.
- Rejected COD could recover to approved after a real return/re-ship/deliver flow.
- The legacy COD migration used 100-row candidate pages, one transaction per order, locked revalidation, exact-one child checks, and an intentional forward-only `down()` policy.
- Receipt issuance was moved to `Order::canIssueReceipt()` for the controller and admin controls, with an operator document and changelog entry.

Incomplete or unsafe inherited pieces corrected during this continuation:

- The payment-aware guard treated a missing payment row as fulfillable and accepted refunded COD. New RED tests reproduced both bypasses; fulfillment now fails closed for missing, refunded, unapproved, or inconsistent payment truth.
- Existing shipping success tests used impossible no-payment fixtures. They were updated to coherent pending, rejected, or approved COD truth; their behavior assertions were preserved.
- Repeating Delivered on an already-delivered legacy rejected COD record changed it to approved without a re-shipment event. A RED regression reproduced this. Settlement now requires an actual shipment-status change, while the valid returned → shipped → delivered recovery remains green.
- The member order view still duplicated the receipt predicate. It now uses `Order::canIssueReceipt()` and is covered alongside the endpoint and backoffice control.
- The affected unit lifecycle fixtures now enter shipment-owned transitions through `updateShipment()` with realistic payment/shipment state instead of bypassing the new ownership boundary.

The plan and progress ledger were not edited.

## Finding-by-finding correction and evidence

### 1. Rejected transfer/QR expiry

`orders:expire` now selects pending-payment orders whose order and payment truth are either pending or rejected, limited to bank transfer/QR. `OrderWorkflowService::expire()` repeats those checks under an order lock followed by a payment lock, then uses the existing cancellation/stock-return path.

Coverage rejects both bank transfer and QR, expires them, verifies stock returns from 7 to 10 exactly once, verifies one inventory log and one order transition, retries expiry, and confirms later approval cannot revive the cancelled order. A stale candidate whose payment became approved is rejected by both direct locked revalidation and command selection.

### 2. Generic paid bypass

The shared `transition()` boundary permits a changed transition to paid only when the source is `payment_approval`, both order and payment already say approved, and the method is bank transfer/QR. The controller writes those approval fields in the same outer transaction; any workflow failure rolls everything back.

Single and bulk endpoints are covered for pending and rejected bank transfer/QR. Both reject direct paid requests without state or activity changes. Admin list/detail controls omit unsafe paid targets. Legacy unapproved transfers cannot pack. Missing payment rows, inconsistent state, and refunded COD also fail closed at the shared fulfillment boundary.

### 3. Usable shipment workflow and no generic shipment/COD bypass

Shipment-owned order edges (`preparing`, `shipped`, `delivered`, `delivery_failed`) cannot be changed by generic single/bulk endpoints. The order detail Shipping form calls `admin.shipping.update`, exposes only valid targets, carries carrier/tracking fields, and is linked from COD payments. Packing remains the one generic fulfillment edge because the shipment schema has no packed state.

The rendered-control integration test follows both a new COD order and an approved transfer through Preparing → Packed → Shipped → Delivered, choosing the action visible in the page at each step. It verifies synchronized order/shipment truth, timestamps, approved payment truth, and receipt access. Direct generic attempts across every shipment edge remain rejected.

### 4. COD return/retry settlement

Shipment update locks order → shipment → payment and owns the order transition, shipment timestamps, payment settlement, and COD settlement activity in one transaction. Returned changes pending COD to rejected; a later actual re-shipment followed by delivery changes rejected to approved and records the new actor/time. Repeated delivery preserves payment, shipment timestamps, and activity.

The integration test covers returned → shipped → delivered, refusal of manual COD approval between attempts, both settlement actors, timestamp/idempotence preservation, and receipt access. A controlled payment-write failure proves order, shipment, timestamp, payment, and activity rollback together. A separate RED/GREEN regression proves same-state Delivered cannot rewrite a legacy rejected payment.

### 5. Legacy pending COD reconciliation

`2026_09_25_000100_reconcile_legacy_pending_cod_orders.php` pages pending-payment/pending candidate IDs by 100 and revalidates each under a short transaction using the common order → shipment → payment lock order. It converts only an order with exactly one untouched pending shipment and exactly one unreviewed pending COD payment, clears `expires_at`, and changes no payment/history/stock/amount fields.

The upgrade fixture creates 101 eligible rows to cross the page boundary, runs `up()` twice, and verifies all eligible rows become confirmed exactly once. It preserves paid, preparing, packed, shipped, delivery-failed, cancelled, delivered, transfer, rejected, approved, reviewer-only, verified-time, restocked, cancelled-at, and shipped-shipment cases. A migrated row then enters the real shipping flow.

`down()` is deliberately data-no-op: restoring pending-payment would recreate the stranded lifecycle, while the previous expiry deadline cannot be reconstructed. The fixture verifies `down()` does not reverse migrated or subsequently progressed rows. Operations documentation requires a restorable backup for exact rollback.

## Ownership and lock design

- Generic order transition: order first; payment only for payment-aware paid/packing validation; inventory only when cancellation restores stock.
- Payment review: order → payment. Approval state plus paid transition commit atomically. The locked payment/order association is rechecked.
- Shipment update: order → shipment → payment. The locked shipment association is rechecked; an association change is rejected without locking a second order.
- Expiry: order → payment → inventory on cancellation.
- Legacy reconciliation: order → shipment → payment, one candidate transaction at a time.
- No path holding payment subsequently acquires shipment. Same-order operations serialize on the order lock, avoiding the former shipment-first/payment-review inversion.
- Checkout only creates new order/payment/shipment rows after its existing user/cart/inventory locks; it does not lock an existing order in the reverse direction.

## Receipt policy

The preserved policy is fulfillment-based: an order may issue a receipt when its status is paid, preparing, packed, shipped, or delivered, or when order payment truth is approved/paid. A receipt is not independent evidence that COD cash was collected. Therefore historical delivered COD with pending or rejected payment remains receiptable. `Order::canIssueReceipt()` is now used by the receipt endpoint, member order control, admin order control, and payment control.

## TDD and verification evidence

The final branch review contains the original fresh SQLite pre-fix probes for all five findings. The inherited diff also makes the pre-fix behavior explicit: expiry required pending/pending, generic transition accepted paid, shipping had no rendered action and settled only pending COD, redelivery skipped rejected COD, and the confirmed-status migration had no data repair.

Additional RED evidence produced during this continuation:

- Missing payment and refunded COD boundary tests: 2 tests, 2 expected failures (`Session is missing expected key [error]`). After the fail-closed predicate: 2 tests, 13 assertions, green.
- Legacy same-state Delivered/rejected COD test: expected rejected but observed approved. After requiring a shipment status change: the legacy preservation and valid retry tests passed together, 2 tests / 20 assertions.
- A focused compatibility run exposed four old shipping success fixtures with no payment. Root cause was fixture invalidity under the approved payment-aware contract; coherent COD fixtures restored all 9 shipping tests / 83 assertions without weakening the boundary.

Fresh final checks on the committed implementation tree:

| Check | Result |
| --- | --- |
| Focused workflow PHPUnit set | PASS — 91 tests / 876 assertions |
| Full PHPUnit suite | PASS — 144 tests / 1,153 assertions |
| `pint --test --no-ansi --diff=4d31222` | PASS |
| Pint for the two new PHP files | PASS |
| `php -l` across changed non-Blade PHP | PASS — 35 files / 0 failures |
| `artisan view:cache` then `view:clear` | PASS |
| `artisan route:list --except-vendor` | PASS — 86 routes, including `admin.shipping.update` |
| `git diff --check 4d31222` and staged diff check | PASS |
| Named throwaway SQLite `migrate:fresh` → rollback last migration → migrate again | PASS; throwaway file verified and removed |
| MySQL grammar compilation | PASS — candidate query uses `LIMIT 100`; order/shipment/payment locks compile with `FOR UPDATE`; update uses parameterized MySQL SQL |

PHPUnit configuration forces SQLite `:memory:` and `Tests\TestCase` aborts unless the resolved driver/database match. The Artisan migration probe used a separately named SQLite file inside this worktree. No MySQL PDO connection, server, schema, or retained smoke database was opened.

## Residual operational risks

- The data migration's query shape was compiled with Laravel's MySQL grammar, but this continuation intentionally did not execute it on MySQL. Live upgrade timing and InnoDB blocking remain deployment checks; prior Task 8 evidence is not reclassified as fresh evidence here.
- The migration is forward-only at the data layer. Take a restorable backup before deployment; do not rely on `down()` to recreate lost expiry deadlines.
- The scheduler must remain active for rejected transfer/QR expiry to run.
- Existing public legacy slips remain an operational migration concern outside these five workflow findings.
- Receipt PDF serving still depends on GD in the serving PHP runtime.
- The validation gate is changed-range Pint; unrelated whole-repository formatting debt remains outside this integration fix.

## Commits

1. `7a246db fix: close commerce workflow integration gaps` — implementation, migration, regressions, UI, changelog, and operator documentation.
2. `docs: record final commerce integration fix` — this report only.
