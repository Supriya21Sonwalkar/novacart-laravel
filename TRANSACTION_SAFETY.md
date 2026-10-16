# Transaction and failure handling

This project stays on PHP 7.2 and Laravel 7.2. No schema changes or database reseeding are needed for this update.

## Preferred transaction structure

Use `DB::transaction(function ($connection) { ... }, $attempts)` for related writes. Laravel 7.2 runs the transaction callback, commit and retry loop. The registered MySQL/SQLite connection extensions preserve scoped rollback and original-error guarantees through failure hooks. An outer try covers begin failures as well.

All 49 application transaction calls now use Laravel's DB::transaction API. No Laravel/vendor files are modified. Single-statement writes and normal reads are left alone unless they participate in a larger operation. Exceptions from those paths use Laravel's central handler.

- Each operation owns only the scope it started. Nested operations use savepoints and cannot commit/roll back the caller's work.
- Callback code must not call commit, rollback, DDL or switch the default connection. It must throw on failure; never catch and continue with incomplete business data.
- The callback return value is preserved. Existing retry counts are preserved; only known concurrency errors are retried, by the outer owner after safe rollback.
- MySQL may abort the entire transaction on a nested deadlock. The failure propagates to the outer retry owner; invalid connection bookkeeping is discarded.
- Failed rollback/disconnect/logging cannot replace the original error. A failed cleanup prevents retry.
- An exception after commit is not rolled back or retried. Lost commit acknowledgements are inherently uncertain: inspect the idempotency key / saved records before resubmitting.
- Retried callbacks must reload mutable Eloquent models, lock rows in a consistent order, and contain database work rather than emails, uploads or API calls.

## Operations covered

Existing boundaries for checkout, order status/cancellation, payments, inward receipts/imports, purchase receipts, outward transitions, returns, rider assignment/service areas, registration/profile/address changes, bulk packing and isolated stock workers now use DB::transaction with the registered connection safeguards.

Additional boundaries protect admin record writes plus their audit log, admin user-role synchronization, product soft deletion plus audit, cart creation/update, wishlist toggles, guest-cart merge, reorder, password change plus audit, and the combined full-return request/approval/inspection operation.

ProductWriter reloads/locks existing products before stock calculation, keeps FIFO adjustments, batches, gallery and audit in one transaction, and reloads on retry. Customer profile/default-address updates serialize through the user row. Inward products are locked in product-ID order.

## Files, imports and notifications

ProductWriter and ReviewWriter store the new upload once outside retryable database work. If saving fails, they delete only the new unreferenced file. They retain a potentially referenced file when database confirmation is unavailable, and log a cleanup review warning. Existing/shared images are not deleted. These upload services should own their production operation boundary: an unrelated outer caller must handle its own later rollback and file compensation.

CSV handles and XLSX archives are closed using finally. Import confirmation tokens are constrained to UUIDs. A missing preview cannot be presented as ready. If receipt records commit but writing the preview confirmation fails, the UI explicitly says the records were saved and warns against resubmission. It does not claim a database rollback reversed a file operation.

Account/profile verification notifications run after the account/profile transaction. A delivery failure is reported truthfully without claiming the already-saved account failed to exist.

The order outbox inserts remain part of the order transaction. The worker claims each message in a short transaction, releases its locks, sends mail, then records the result in another short transaction. Attempts are limited to five with backoff. Real external transport is refused inside a caller's open transaction.

A worker crash or acknowledgement failure leaves Sending. An expired Sending lease becomes Uncertain and is NOT automatically resent: check delivery/provider records before operator recovery. An email transport can also throw after a provider accepted a message; ordinary retries cannot guarantee exactly-once delivery without provider idempotency/reconciliation.

Backup/restore commands report failures with nonzero exit codes, close streams and retain incomplete artifacts for diagnosis. A completed backup is announced only after the SQL, uploads, hashes and manifest are finalized. Restore verification still creates an isolated schema, never replaces the working database. Scheduler iteration failures are reported without silently stopping the local loop.

## Central errors and logging

Validation, authorization, not-found and existing expected errors retain Laravel's normal responses. Unexpected errors show safe HTML/JSON with a reference when APP_DEBUG=false. Local debug behaviour is retained.

Error views skip the storefront database composer, so a database outage cannot make the error page query the same failed database. QueryException reporting excludes SQL/bindings/raw driver messages, retaining SQLSTATE and stack frames without arguments. Secondary cleanup logs contain exception classes, not credentials. The original exception remains the one rendered even if logging fails.

## Verification performed

The PHPUnit configuration and CreatesApplication guard require SQLite :memory: and refuse the working MySQL database. The test suite includes real midway-failure injection after writes, not just mocked success responses.

New coverage includes transaction return values, Throwable rollback, begin/commit/rollback failures, caller-scope preservation, nested server-aborted deadlocks, root-only retries, post-commit exceptions, safe error responses/logging, checkout/cart/payment/stock rollback, inward and purchase totals, outward status, cancellation, return inspection, user-role sync, image cleanup, outbox retry/uncertain acknowledgement, and post-commit verification-mail failure.

Run with PHP 7.2:

```powershell
& "C:\xampp\php\php.exe" vendor/phpunit/phpunit/phpunit
```

An additional `store:check-concurrency` check runs two competing MySQL workers in a newly named isolated schema; it does not use the store tables for fixtures.

## Remaining limits

Rollback only reverses uncommitted transactional database changes on the owned connection. It cannot reverse committed records, files, sent emails, real payments, external API calls or another connection's changes. IDs may have gaps after failed inserts. Disconnection/commit uncertainty and outbox Uncertain states need reconciliation. Do not run migrations/DDL inside these business transactions.

No real payment/courier integration, provider-level exactly-once messaging or runtime upgrade was introduced. Existing sandbox behaviour, permissions and the separately flagged imported stock discrepancies remain unchanged.

## Final results (15 October 2026)

- 133 tests / 1,002 assertions passed, including 36 added transaction/failure tests (after the controller follow-up).
- PHP 7.2 syntax checks passed for 33 changed/new PHP files; git diff whitespace check passed.
- Isolated MySQL two-worker contention test passed: exactly one stock allocation, no negative stock.
- Backup created at storage/app/backups/20261015-150929-8bd9faff.
- Working database remains novacart_merged_20261015 with 162 products, 14 orders and 16 inwards. No live schema or catalogue/stock records were changed by the tests.
- Homepage, category/subcategory listing, customer login and admin login returned HTTP 200 after the changes.

## Changed files and responsibilities

- `app/Console/Commands/BackupStore.php`: Use shared transaction boundaries or explicit command/resource failure handling.
- `app/Console/Commands/CheckStockConcurrency.php`: Use shared transaction boundaries or explicit command/resource failure handling.
- `app/Console/Commands/StockCheckWorker.php`: Use shared transaction boundaries or explicit command/resource failure handling.
- `app/Console/Commands/VerifyStoreBackup.php`: Use shared transaction boundaries or explicit command/resource failure handling.
- `app/Console/Commands/WorkStoreSchedule.php`: Use shared transaction boundaries or explicit command/resource failure handling.
- `app/Exceptions/Handler.php`: Safe centralized errors, log redaction/reference and database-independent error views.
- `app/Http/Controllers/AccountController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/AdminController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/AdminVendorController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/AdminWorkspaceController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/BatchController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/DeliveryController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/InwardController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/OutwardController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/PurchaseListController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/ShopController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/ShoppingIntentController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Http/Controllers/VendorController.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Providers/AppServiceProvider.php`: Safe centralized errors, log redaction/reference and database-independent error views.
- `app/Services/Commerce.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Services/CustomerProfile.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Services/OrderMail.php`: Short claim/acknowledgement transactions, send outside locks, retry/uncertain states.
- `app/Services/OutwardFlow.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Services/PaymentFlow.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Services/ProductWriter.php`: Locked product save, FIFO/stock/gallery/audit atomicity and upload compensation.
- `app/Services/ReturnFlow.php`: Use shared transaction boundaries; keep related writes and existing workflows together.
- `app/Services/ReviewWriter.php`: Owned review save and notification atomicity with upload compensation.
- `app/Services/XlsxReader.php`: Always close XLSX archive handles on failure.
- `app/Support/PostCommitNotification.php`: Account/profile verification delivery failure after the database save.
- `app/Database/PreservesTransactionFailures.php`: Connection hooks preserving scoped rollback, root-only retries and original exceptions while delegating execution to Laravel.
- `app/Database/MySqlConnection.php` and `app/Database/SQLiteConnection.php`: Driver extensions registered in AppServiceProvider.
- `app/Support/SecondaryFailure.php`: Safe cleanup-failure logging, independent of transactions.
- `resources/views/errors/store-failure.blade.php`: Safe centralized errors, log redaction/reference and database-independent error views.
- `tests/Feature/TransactionFailureTest.php`: Isolated failure injection, nested/rollback/retry and external-effect tests.
- `tests/Feature/TransactionScopeTest.php`: Isolated failure injection, nested/rollback/retry and external-effect tests.

## Controller audit follow-up

A second controller review found and closed these additional compound-write gaps. No live schema/data migration was required.

| Controller | Follow-up |
|---|---|
| AccountController | Password change and password-reset token removal now share a transaction with user/token row locks. Current-password changes recheck the supplied password after taking the row lock. Reset-link delivery has truthful failure handling outside a retryable SQL transaction. |
| StoreOperationsController | Expense insert and its audit log are atomic. |
| DeliveryController | Rider-zone replacement locks and revalidates the rider. Rider assignment locks the order before the rider and permits bounded concurrency retries. |
| AdminVendorController | Legacy multi-recipient announcements and their audit log are atomic. The disabled portal was not enabled. |
| VendorController | Legacy product/profile uploads use try/catch with unreferenced-file compensation; related SQL and audit use the shared scoped rollback handler. Settings synchronize user/store atomically. Deactivation, review response and shipping proposal updates are atomic with audit, and shipping status is checked under lock. |

Controller actions already delegating to checkout/payment/inward/outward/return services continue to use their existing atomic boundaries. Ordinary reads and isolated single SQL writes remain unwrapped; Laravel's centralized handler preserves validation/authorization/not-found responses. Literal rollback calls are deliberately centralized in the registered database connection failure hooks rather than duplicated outside the owner, where they could undo a caller's work.

Additional failure tests cover expense/audit rollback, password/token rollback plus token reuse rejection, reset-mail failure, vendor settings/product/logo/announcement failures, and rider-zone replacement rollback. Disabled vendor methods are tested directly with isolated fixtures; the real vendor routes remain disabled.

Final controller follow-up verification: all 133 tests / 1,002 assertions passed after formatting. PHP 7.2 syntax checks passed for 33 files; formatting preserved their parsed code structure.

- `app/Http/Controllers/StoreOperationsController.php`: expense and audit write transaction added during the controller follow-up.


## Native DB transaction migration (16 October 2026)

49 application boundaries were migrated without changing callbacks, locks, return values or retry arguments (verified by parsed-code comparison). The old transaction helper is removed. Cleanup logging is independent in SecondaryFailure. Controllers retain their explicit try/catch blocks. Native Laravel 7.2 has different commit/rollback failure semantics, so thin MySQL/SQLite connection extensions preserve the previously tested guarantees; this is not an unmodified stock-connection configuration. Other database drivers are not configured with these guards. No UI, routes, migrations, production data or runtime versions were changed.

Additional tests exercise the DB facade, named connections, lazy MySQL factory registration, begin-listener failure and post-commit listener failure. Existing ownership, nested deadlock, rollback-failure and business failure tests now exercise Laravel transactions, rather than the removed helper.

Verification for this migration: 138 tests / 1,022 assertions passed on isolated SQLite :memory:, including the original 36 transaction/failure cases and five new facade/registration tests. PHP 7.2 syntax checks passed for all 37 relevant PHP files. git diff --check passed. The isolated MySQL contention check passed in novacart_concurrency_20261016_114931_5c7b23 (retained for inspection); one stock allocation succeeded and the competing request was blocked. No working-store schema or records were modified.

Review limitation: two unrelated OneDrive placeholders, app/Http/Middleware/RedirectIfAuthenticated.php and app/Providers/BroadcastServiceProvider.php, could not be read during the broad scan (cloud operation aborted). They were not edited; all changed files were readable and verified.
