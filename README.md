# NovaCart — PHP / Laravel ecommerce demo

This is the PHP rebuild: **Laravel Framework 7.2.0**, **PHP 7.2.34**, Blade templates, MySQL for the configured local store, and small amounts of plain JavaScript. The original React/TypeScript site in `../novacart` is a separate project. Its published URL does not serve this PHP application.

## Current MySQL database

The local store uses XAMPP MySQL/MariaDB at `127.0.0.1:3306`, database **novacart**. View its 31 tables in http://localhost/phpmyadmin/. XAMPP Apache and MySQL must be running. All four Laravel migrations have run and the original SQLite records have been copied without removing the SQLite file. Login credentials remain unchanged.

For this local XAMPP installation, `.env` uses `DB_CONNECTION=mysql`, `DB_DATABASE=novacart`, `DB_USERNAME=root`, and an empty `DB_PASSWORD`. The setup script preserves this MySQL connection. A fresh source copy defaults to SQLite unless configured for MySQL first. To set up MySQL elsewhere, create an empty `novacart` database with `utf8mb4_unicode_ci`, set your own MySQL credentials in `.env`, and run `php artisan config:clear` followed by `php artisan migrate --seed`. The one-time `scripts/import-sqlite.php` refuses to overwrite a non-empty target; use it only immediately after migrating an empty target, before seeding, if transferring an existing SQLite store.

## Run on this computer

Dependencies and the demo database have been prepared. From this folder run:

```powershell
php artisan serve --host=127.0.0.1 --port=8007
```

Open http://127.0.0.1:8007. PHP is installed at `C:\xampp1\php\php.exe`.

**Demo administrator:** `admin@novacart.test` / `DemoStore!2026`.

Sign in, open **Admin panel → Products → Add product**. Enter the name, SKU, rupee price, stock and comma-separated variants. Upload an image or enter an image URL; add gallery URLs if desired. Products appear on the storefront when Active is checked. Change the store name, tax and other store content in **Settings**. NovaCart is a placeholder until you supply your store name.

## Install a fresh copy

Use PHP 7.2.5 or later within the compatible PHP 7 range, Composer, PDO SQLite, OpenSSL, fileinfo, DOM and XML extensions. The exact package lock was verified on PHP 7.2.34.

```powershell
composer install
.\scripts\prepare-local.ps1
php artisan serve --host=127.0.0.1 --port=8007
```

If PowerShell blocks scripts, perform the equivalent steps manually: copy `.env.example` to `.env`, set `DB_CONNECTION=sqlite` and `DB_DATABASE` to an absolute path to an empty `database/database.sqlite` file, set `MAIL_MAILER=log`, then run `php artisan key:generate`, `php artisan migrate --seed`, and `php artisan storage:link`. Give PHP write access to `storage` and `bootstrap/cache`. On Windows the storage link may require Developer Mode or a symlink-capable account. The link works on this computer.

The setup script preserves an existing application key and database. Seeding preserves existing settings/products rather than overwriting admin edits. Optional `DEMO_ADMIN_EMAIL` and `DEMO_ADMIN_PASSWORD` environment variables control the initial seeded admin. Do not use the published demo credentials for a real store.

## Included

Customer: home, categories, product listing and detail/gallery, variants, search and filters, sorting, pagination, recently viewed products, related products, wishlist, persistent carts, coupons, addresses/default address, profile-required account checkout, order history/status/timeline, cancellation, seven-day return requests, reorder, printable invoices, moderated reviews/images, notifications, contact/FAQ/policies and newsletter signups.

Accounts: required full name, email, mobile number and default delivery address (street address, city, state, PIN code and address type), email/password registration and sign-in, email verification, password reset, profile and password changes, active/inactive accounts. Registration assigns the customer role on the server. Browsing products and viewing the bag are available without signing in or completing a profile. Adding products to the bag or wishlist, and checkout, require a signed-in account with a complete profile. These actions show a dismissible Complete your profile first popup with Keep browsing and Sign in / Complete profile buttons. Guest checkout is disabled. Incomplete accounts are redirected to Your account, where all required details must be saved. Profile completeness is validated from current user and owned default-address records for every cart addition, wishlist addition, quantity update, reorder and checkout, including direct requests. Deleting the default address blocks shopping until it is replaced. Legacy guest records from that browser session are associated with the account after profile completion.

Admin: dashboard and sales chart, product/image/gallery management, categories/subcategories, brands, users, role permissions, orders/status, inventory/stock adjustments and history, coupons/date and usage limits, shipping methods, shipment tracking, test transaction/refund records, review approval/replies, reports, CSV export, banners, settings, support inbox and activity history. Sidebar visibility and server access use the assigned role permissions.

Checkout rechecks current prices and stock, calculates totals on the server, writes immutable order/address snapshots in a database transaction, and prevents duplicate submissions with an idempotency key. Cancellation/refund restocks at most once. Addresses, reviews and orders are scoped to the signed-in user or guest session.

## Demo boundaries and configuration

- All payments, refunds and order values are **test records**. No card/UPI data is collected, and no money moves. Integrate a provider and verified webhooks before real checkout.
- Mail uses Laravel's **log** driver locally. Verification/reset links appear in `storage/logs/laravel.log`; configure SMTP in `.env` for actual delivery. Contact messages and newsletter opt-ins are saved for the admin; campaigns are not sent.
- Shipping rates, zones and estimated days are admin records. The demo supports India addresses and INR; it does not connect to a carrier or enforce regional carrier routing.
- Invoices and reports support browser print / Save as PDF. CSV files open in Excel. Native XLSX generation and statutory invoice numbering are not included.
- Related/recent product suggestions are simple catalogue rules. Multiple languages/currencies, mobile app API, queued email campaigns and scheduled backups remain optional integrations. Back up the database and public uploads separately.
- Sample product photos are external URLs from hotsound.cstatic.io, portpearl.com and craftclothing.ph, for demo display only. Replace them with your own licensed product images.
- Laravel 7.2.0 and PHP 7.2 are legacy releases with known security advisories. Composer has **specific, documented install exceptions** for this requested local demo; `composer audit` continues to report them. Upgrade and review dependencies before a public launch. `App\Compat\ComposerPackageManifest` adapts Laravel 7.2's package discovery to Composer 2 metadata without changing vendor files.

Deploying this version requires a PHP-capable host with its document root set to `public`, appropriate database/environment configuration and HTTPS. The previously published Sites URL remains the earlier TypeScript implementation.

## Field validation

Registration and profile updates validate full names, email format and uniqueness, Indian mobile format and uniqueness, street address length, city/state names, six-digit PIN codes and address type. Phone numbers are normalized to 10 digits, so +91 variants cannot create duplicate accounts. The users_phone_unique database index enforces this during concurrent requests too. Passwords require 8–72 characters, uppercase, lowercase and a number; confirmation must match. Existing users may keep their own number when updating their profile. Multiple delivery addresses may share a recipient phone number; account phone numbers are unique.

## Verify

```powershell
php vendor/phpunit/phpunit/phpunit --testdox
php artisan view:cache
composer check-platform-reqs
```

Tests use a separate in-memory SQLite database and do not modify the local demo data. See `tests/Feature/CommerceTest.php` for checkout, stock, ownership, admin product management, role assignment, review moderation and page rendering checks.
