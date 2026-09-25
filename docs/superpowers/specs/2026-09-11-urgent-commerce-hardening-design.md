# Urgent Commerce Hardening Design

Date: 2026-09-11

## Purpose

Make the Rai Khaisaeng Strawberry Laravel application safe and reliable for local operation through VS Code, XAMPP, Composer, and MySQL. Railway deployment is explicitly out of scope because the trial deployment is no longer active.

This phase addresses the urgent findings that can corrupt stock, expose payment evidence, erase transaction history, or allow inconsistent order states. It also establishes automated regression tests for those paths.

## Scope

1. Centralize order lifecycle rules and reject invalid status transitions.
2. Expire unpaid orders atomically and return reserved stock exactly once.
3. Keep payment approval and rejection consistent with order state inside database transactions.
4. Distinguish confirmed cash-on-delivery orders from unpaid transfer orders.
5. Store new payment slips privately and serve them only through authorized controllers.
6. Preserve historical orders when a customer account or category is removed.
7. Hide demo credentials outside the local environment.
8. Add automated tests for authorization, stock, expiry, payment, deletion, and double submission.
9. Document the local scheduler and test commands for XAMPP.

## Out of Scope

- Restoring or replacing Railway hosting.
- Adding a payment gateway, dynamic PromptPay, refund workflow, or slip re-upload flow.
- Adding new marketing, SEO, review, coupon, wishlist, or shipping-carrier features.
- Moving product media to cloud object storage.
- Building the Knowledge Base workflow; that work resumes after this hardening phase.

## Architecture

### Order lifecycle service

Create a single application service responsible for allowed order transitions and stock restoration. Controllers and console commands must call this service rather than updating order status directly.

Allowed forward transitions:

- `pending_payment` to `paid` or `cancelled`
- `confirmed` to `preparing` or `cancelled`
- `paid` to `preparing` or `cancelled`
- `preparing` to `packed` or `cancelled`
- `packed` to `shipped` or `cancelled`
- `shipped` to `delivered` or `delivery_failed`
- `delivery_failed` to `shipped` or `cancelled`
- terminal states `delivered` and `cancelled` do not transition

The service will lock the order row inside a transaction, validate the transition, update timestamps, restore stock when entering `cancelled`, and write inventory and activity records. The existing `stock_returned_at` guard remains the idempotency key so retries cannot add stock twice.

Shipment updates must map only to valid order transitions. They may not move a cancelled or delivered order backward.

### Order expiry

Replace the bulk status update in `orders:expire` with per-order processing through the lifecycle service. The command selects expired `pending_payment` orders in batches, locks each order, transitions it to `cancelled`, and restores stock exactly once.

Only transfer and QR orders in `pending_payment` may expire. COD orders start in `confirmed`, have no expiry timestamp, and are never selected by this command.

Register the command with Laravel Scheduler. For local use, documentation will instruct the operator to run `php artisan schedule:work` in a dedicated VS Code terminal while the shop is active. Running `php artisan orders:expire` manually remains supported.

### Payment consistency

Payment review will lock both payment and order within one transaction.

- Approval is allowed only for a pending payment attached to a non-cancelled `pending_payment` order. It marks the payment approved and transitions the order to `paid`.
- Rejection is allowed only for a pending payment on a non-terminal order. It marks `payment_status` rejected while keeping the order in `pending_payment` until expiry or a later slip re-upload feature.
- Repeated approval or rejection is idempotent and produces no second transition.
- COD checkout creates a `confirmed` order with pending payment and no expiry. A delivered COD shipment marks payment approved; a returned COD shipment marks payment rejected. This keeps fulfillment and payment truth separate without pretending COD was prepaid.

### Private payment slips

New slips will be saved to a private local storage location, not the public disk. Add authenticated admin endpoints for viewing or downloading a slip. Authorization remains restricted to operational roles that can access payment review.

Legacy demo/public slip paths remain readable through the authorized endpoint during transition, but templates will no longer expose their direct public URLs. Add a command to migrate existing real public payment slips into private storage without touching bundled demo assets. The command supports a dry run and reports missing files.

HTTP responses use an inline image disposition for preview and safe content types. User-supplied paths are never accepted from route parameters.

### Historical data preservation

Customer deletion rules:

- A customer with orders cannot be hard-deleted from the admin UI or controller.
- The administrator is directed to suspend the account instead.
- A customer without orders may still be deleted.
- Existing database foreign keys remain unchanged in this phase to avoid a destructive migration, while application tests enforce the guard.

Category deletion rules:

- A category containing products cannot be deleted.
- The administrator is directed to deactivate the category or move its products first.

These guards prevent cascade deletion of business history without redesigning existing tables.

### Demo data visibility

The login page displays demo credentials only when `APP_ENV=local`. Demo seeding protection remains unchanged. No credentials or secrets are added to source control.

## Error Handling and User Feedback

- Invalid order transitions return a clear validation-style message and do not partially modify payment, shipment, order, or inventory records.
- Missing slip files return 404 without revealing filesystem paths.
- Unauthorized slip access returns 403.
- Expiry processing logs individual failures, continues safely, and reports successful and failed counts.
- Account and category deletion guards explain the safe alternative.

## Tests

Add the Laravel testing dependencies and configuration required by the currently stripped-down project. Tests use an isolated test database and must never point at the development database.

Required feature coverage:

- member cannot access another member's receipt or order
- member cannot access private payment slips
- staff/admin payment access follows current role policy
- concurrent-style repeated checkout cannot create duplicate orders or overdraw stock
- expired pending order is cancelled and stock is restored once
- repeated expiry does not restore stock twice
- COD order does not expire and can progress from `confirmed` to fulfillment
- invalid order state changes are rejected
- approving a cancelled or terminal order is rejected
- payment approval updates payment and order atomically
- customer with orders cannot be deleted
- category with products cannot be deleted
- demo credentials render locally and remain hidden outside local mode

Unit tests cover the lifecycle transition matrix. Feature tests cover HTTP authorization and persistence behavior.

## Local Operating Workflow

Use three VS Code terminals when exercising the full local system:

1. XAMPP runs Apache/MySQL, or Artisan serves HTTP if preferred.
2. `php artisan schedule:work` processes expired orders.
3. Test and maintenance commands run separately.

The README will show the exact XAMPP PHP and local Composer commands already used by this repository. It will also explain how to create a dedicated test database and verify that its name is different from the development database before tests run.

## Acceptance Criteria

- No new payment slip is directly accessible beneath `/storage`.
- All payment, shipment, admin status, and expiry changes obey one lifecycle definition.
- Cancelling or expiring an order returns stock once and produces inventory history.
- Deleting a customer or category cannot cascade-delete existing business data through the application.
- Demo credentials are absent when the application environment is not local.
- The required automated tests pass against an isolated database.
- Existing storefront, checkout totals, receipts, admin pages, and bilingual behavior continue to work.

## Rollout

Implement in small commits: test foundation, lifecycle and expiry, payment consistency, private slips, deletion guards, environment visibility, then documentation and regression verification. Existing uncommitted UI/backoffice improvements will be preserved and committed separately so the hardening diff remains reviewable.
