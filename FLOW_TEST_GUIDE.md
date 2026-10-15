# NovaCart local demo: strengthened flow

PHP 7.2 and Laravel 7.2 are unchanged, as requested. This remains a local test store, with no real payments or automatic courier integration.

## Start

Start MySQL in XAMPP. In PowerShell, from the project folder, run `./START-NOVACART.ps1`. This starts the web server and a background scheduler together; keep the server terminal open. The scheduler stops when the launcher exits.

If you start the server manually with `php artisan serve`, also run `C:\xampp\php\php.exe artisan store:work` in another terminal. Otherwise automatic payment expiry, order email processing and daily backups will not run.

## Customer checkout and payments

1. Add valid stock through Inward. Perishable products must be marked fresh/dairy and have actual batch expiry dates. Existing batches without expiry dates are blocked, not deleted.
2. As a customer, browse and select a product. If sign-in/profile completion is required, the selected product is remembered. After completing the profile, confirm the action using the current price and stock. Nothing is automatically bought or added without confirmation.
3. Choose **Online payment (sandbox)** at checkout to test payment outcomes. The order reserves its FIFO allocation for 15 minutes. Available stock falls once during reservation.
4. On the customer order page, simulate success. Payment becomes Paid (sandbox), and admin can prepare and dispatch the order. Confirmation does not deduct stock again.
5. Repeat with a new order and simulate failure. The order is cancelled and the original batch allocation is restored once.
6. Repeat and leave payment pending. The scheduler expires it after 15 minutes and restores its stock. You can run `artisan store:expire-payments` manually to process due reservations. A late success cannot reactivate an expired order.
7. Existing **Test card / UPI** and **Cash on delivery (test)** methods remain available for the previous demo flow. No payment data is collected and no money moves.

## Admin order workspace

Open **Orders → Process order**, or a recent order on the dashboard. The workspace combines items, totals, separate payment status, customer address, allocated batches, outward packing/dispatch, carrier/rider details, delivery proof and return records.

1. A Pending sandbox payment blocks preparation through the workspace and blocks dispatch through every route.
2. Click **Prepare order outward**, review the FIFO batches, and **Mark packed**.
3. Save carrier plus tracking number, or assign a valid rider with an existing delivery schedule.
4. **Dispatch order** changes the customer order to Shipped. Skipping directly from Placed to Delivered or pickup is rejected.
5. For a courier, record handover/carrier delivery evidence when marking delivered. For an assigned rider, follow Picked up → On the way → Delivered and enter proof.
6. Failed delivery reasons are recorded without completing or cancelling the order. Admin can see the issue in the workspace and arrange another attempt.
7. GPS sharing remains an explicit rider action, requiring browser permission. It uses the rider's device location; it does not invent location or traffic-aware ETA.

Cancellation before dispatch restores stock once. Returns remain item/quantity based; inspect goods before restoring usable stock. Delivery proof is an audit note, not cryptographic OTP verification.

## Costs and notifications

Open **Costs & operations** in Admin. FIFO allocation costs and inspected returns produce a merchandise margin; entered operating expenses produce an indicative result. Tax and delivery income/cost are excluded. These figures are not statutory accounts. Unknown historical costs withhold the margin instead of being treated as zero.

Order events are queued into `store_mail_outbox`; the worker retries failures up to five times with backoff. With the current log mail driver, success is labelled **Logged**, not Sent. Actual email needs SMTP configuration. SMTP delivery is at least once under crash recovery; this is not a guaranteed exactly-once external delivery system.

## Backups and reliability checks

- `artisan store:backup` saves the database, public uploads and a hash manifest under `storage/app/backups`. The scheduler runs it daily at 02:00 while the local runner is on. Backups stay outside the public web root and are ignored by Git.
- `artisan store:verify-backup <backup-directory>` checks hashes and restores into a newly named `novacart_restorecheck_*` schema. It never replaces `novacart`. The check schema is retained for inspection.
- `artisan store:check-concurrency` creates a separate `novacart_concurrency_*` schema, runs two competing MySQL stock workers and checks that only one can take the last unit. This verifies the stock-locking engine, not production load capacity.
- Tests use isolated SQLite memory databases. The existing order, inward, outward, FIFO, cancellation and return tests remain active.

Keep an off-device backup for disaster recovery. Public launch still requires a supported runtime, secured hosting, provider integrations and broader performance/security testing.
