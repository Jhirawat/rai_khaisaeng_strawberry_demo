# Order, payment, and shipping operations

## Operator workflow

Bank transfer and QR orders become paid only through payment approval. Rejected slips remain unpaid and expire at their original deadline, returning stock once. Generic single/bulk order controls offer cancellation and packing where the lifecycle and payment state permit them; they cannot approve payment or bypass shipping. Fulfillment fails closed when the payment row is missing, a transfer/QR is not approved in both records, COD is refunded, or COD order/payment truth disagrees.

Open an order's details (also linked from the payment page) to use the Shipping form. COD starts confirmed; approved transfer/QR starts paid. Choose Preparing in Shipping, choose Packed in the general order control, then choose Shipped and Delivered in Shipping. Carrier and tracking are editable there. Packing leaves shipment status Preparing because the existing shipment schema has no Packed state. Delivered COD means cash was collected; Returned means collection failed. A returned order may be shipped again, then delivered, which changes rejected COD payment to approved. Repeated delivery does not reset the reviewer, review time, shipment times, or add duplicate activity. Settlement history records each changed payment outcome and actor.

## Receipt policy

The existing policy is preserved: receipt issuance follows fulfillment (paid, preparing, packed, shipped, delivered) OR approved/paid payment status. It is not a separate proof of COD collection. Therefore historical delivered COD orders with pending or rejected payment can still issue receipts. A successful retry now synchronizes collection to approved; receipt availability remains unchanged, and repeating Delivered on a legacy rejected record does not rewrite payment review. `Order::canIssueReceipt()` centralizes the policy used by receipt endpoints plus member and backoffice controls.

## Legacy COD upgrade

Run the normal `C:\xampp\php\php.exe artisan migrate` during the normal local maintenance procedure after taking a restorable database backup. The new `2026_09_25_000100_reconcile_legacy_pending_cod_orders` data migration follows the existing confirmed-enum migration. It uses portable query-builder SQL on MySQL and SQLite, pages candidate order IDs by 100, and revalidates each order inside a separate transaction.

Only pending_payment/pending orders with exactly one unreviewed pending COD payment and one untouched pending shipment are changed to confirmed, with expires_at cleared. Cancelled/restocked, reviewed, approved/rejected/refunded, paid, delivered, progressed, ambiguous duplicate, and incomplete records are preserved for individual review. It does not rewrite payment history, order timestamps, stock, or amounts. Re-running is idempotent. Its down() deliberately does not reverse the repair: restoring pending_payment would strand the order, and the previous expiry cannot be reconstructed. Use the backup for an exact data rollback. Rolling back the earlier enum migration still converts confirmed to pending_payment; do not roll back to old application code while operating this lifecycle.

No development or retained MySQL database was accessed during integration verification; upgrade fixtures and fresh schema checks ran against guarded SQLite :memory:. Live MySQL upgrade execution and InnoDB concurrency verification remain operator validation steps.

## Lock ordering

For existing orders, acquire order first, then shipment if needed, then payment; expiry takes order then payment, followed by inventory only when cancelling. Payment review takes order then payment. Shipment updates and legacy reconciliation take order then shipment then payment. A bound shipment's order association is checked again after locking, and an association mismatch is rejected without locking a second order. Payment review's matching association protection remains in place. No path holding payment acquires shipment. Checkout creates new order/shipment/payment rows after its existing user/cart/inventory locks and cannot contend on an already existing order from these paths.
