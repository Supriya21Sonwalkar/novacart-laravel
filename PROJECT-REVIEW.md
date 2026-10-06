# NovaCart project review — 6 October 2026

The normal demo flows work, but this review found important edge cases. Passing the existing tests does not mean the application is ready for real grocery operations.

## What was checked

- Reviewed customer/account routes and controllers, middleware, profile validation, catalog/search, cart, wishlist, pricing/coupons, checkout transactions, orders/returns, addresses, reviews and notifications.
- Reviewed every configured admin module: products, categories, brands, users, roles, inventory, orders, coupons, shipping, payments, shipments, reviews, reports, banners, settings, support and activity logs.
- Reviewed delivery scheduling, rider assignment, GPS update authorization, tracking responses and browser JavaScript.
- Existing PHPUnit suite: **37 tests / 286 assertions passed**.
- Added separate diagnostic probes: **9 tests / 25 assertions passed**. Eight deliberately confirm undesirable behavior; the ninth confirms a deleted product is handled safely in the cart. These are reproduction checks, not regression tests proving fixes.
- MySQL inspection found 34 tables, 7 products, 2 demo service areas, 62 slots and 1 active rider. Only aggregate counts were read.
- Composer's live dependency audit reported security advisories and abandoned dependencies. No exploit assessment was performed.
- Diagnostic tests use SQLite in memory. The live MySQL customer/product/order data was not changed. No business-logic fixes were applied during this review.

## Confirmed problems to fix first

| Priority | Problem and trigger | Impact | Where |
|---|---|---|---|
| High | Edit an existing default address through Saved addresses → Edit, leaving Set as default checked. | The record loses its default flag; profile completeness becomes false and shopping is blocked. The main profile form uses different logic and does not have this particular bug. | `ShopController::addressSave` |
| High | Add a variant to the cart, then remove that variant from the product in admin before checkout. | Checkout still orders the removed variant. It rechecks price and stock but not the current allowed variant list. | `Commerce::checkout` |
| High | Admin changes a delivered order to Confirmed and then Cancelled. | The system accepts backwards stages and restores inventory for a delivered order without confirming a physical return. | `Commerce::status` |
| High | A delivered order becomes Return requested. | Its assigned rider becomes busy again because availability treats Return requested as an active delivery. GPS updates are accepted again and the tracking response treats the order as active. Return and outbound delivery states need to be separate. | `Delivery::availableRider`, `DeliveryController::update/tracking` |
| Medium | Admin creates the same delivery slot twice, or creates overlapping slots. | Both are accepted with independent capacities, potentially increasing capacity for the same time period unintentionally. | `DeliveryController::slotSave` |
| Medium | A product's saved expiry date is in the past; admin opens Edit and tries to deactivate it. | Saving fails expiry validation. Removal works, but deactivation through the editor is blocked unless the expiry date is changed/cleared. | `AdminController::save` |
| Medium | Type a search term in Inventory, Payments, Shipments or Reviews admin search. | The search field is displayed, but those query types do not apply the search term. The diagnostic probe demonstrated it on Inventory. | `AdminController::index` |
| Medium | Choose Standard delivery as the shipping charge plan and express as the dairy delivery time. | Express delivery can be booked using the standard charge. Shipping plan and fulfillment choice are independent; link them if express has a separate fee. | `ShopController::place`, `Commerce::totals/checkout` |

Reproduce those observations using:

```powershell
php vendor/phpunit/phpunit/phpunit audit/ProjectAuditTest.php --testdox
```

After fixing each issue, replace the diagnostic assertion with a regression test asserting the intended behavior.

## Additional source-confirmed gaps

- **Rider availability is global.** There is no rider service-area assignment, online/offline switch, shift or workload routing. An active unassigned user can qualify for both Mumbai and Pune express orders. Consequently, an express promise is an operational estimate, not evidence that a nearby rider exists.
- **Dashboard omits delivery stages.** Packing, Packed, Picked up and On the way are not included in its status-summary list. The customer count includes all accounts, including admin and rider accounts.
- **FAQ is outdated.** It says customers can shop as guests, although cart additions and checkout require a complete signed-in profile. Its tracking explanation still describes only timelines/tracking numbers.
- **Slots have no edit/cancel interface or automated creation.** Demo slots are created for a short upcoming window by a seeder; there is no scheduled replenishment job. Admin can manually create new slots. Rescheduling existing deliveries is absent.
- **Support is an inbox only.** Contact submissions are stored, but there are no ticket states, admin replies or customer conversation history.
- **Expired stock stays in the catalog.** Checkout blocks expired items, but browsing and add-to-cart do not consistently communicate expiry unavailability.
- **Reviews are not purchase verified.** An authenticated customer can review a product without an order for that product.

## Working features and protections observed

Public browsing/search/category descendants, filtering/sorting, registration and login, unique normalized account phones, profile-required shopping, wishlist/cart basics, server-calculated test totals, checkout stock validation, order/address snapshots, idempotent repeated submissions, ownership restrictions, admin permissions, product uploads, review moderation and printable reports are covered by the existing tests.

Capacity is rechecked at checkout and tracked per slot. Cancellation/restocking is guarded against repeating the same stock restoration. Assigned riders are authorized to post GPS; customers cannot read another customer's tracking. A deleted product does not crash the bag page.

This does not prove concurrent MySQL behavior under production load. The SQLite test suite does not exercise MySQL locking, deadlocks, worker queues, real payment callbacks, physical deliveries or background phone GPS.

## Demo limitations, not broken integrations

| Area | Current behavior | Needed for live use |
|---|---|---|
| Payments and refunds | Test database records; no money moves. | Payment provider, signed webhook verification, reconciliation and controlled refunds. |
| Email | `MAIL_MAILER=log`; verification/reset messages go to the local log. | SMTP/email provider and background delivery jobs. |
| Notifications | Stored in the website. | Actual SMS/push/email delivery, retries and preferences. |
| GPS | Rider browser shares location while open and permitted. | HTTPS deployment and a mobile rider app for dependable background tracking. |
| ETA | Rider enters estimated arrival minutes. | Exact destination coordinates and a routing/traffic service; stale/late delivery alerts. |
| Inventory | One stock quantity and one expiry date per product. | Warehouses, batches, expiry-based picking, damaged/returned-stock inspection and cold-chain handling. |
| Variants | Comma-separated choices sharing one price and stock. | Variant/SKU-level prices, images and inventory. |
| Search | Text matching and admin-maintained synonyms. | Typo handling, relevance ranking, product/category suggestion UI and search indexing when catalog size warrants it. |

## Security and deployment

PHP 7.2 and Laravel 7 are outside official support. Laravel 7 security support ended on 3 March 2021. The dependency audit also reports vulnerable packages and abandoned packages including SwiftMailer and Laravel CORS. This is not proof that every advisory is reachable in this app, but public deployment needs a supported stack and dependency remediation.

Official references: [Laravel 7 support policy](https://laravel.com/framework/docs/7.x/releases#support-policy), [PHP unsupported branches](https://www.php.net/eol.php).

Keep demo credentials and demo service-area promises confined to testing. Production also needs HTTPS, appropriate secret/database handling, tested database/upload backups, monitoring and deployment rollback. These were not configured or exercised as part of this review.

## Recommended additions, in order

1. Fix the confirmed business-logic issues and add desired-behavior regression tests.
2. Implement rider area/shift/online status, dispatch rules, slot management and delay/reassignment handling.
3. Add batch inventory, variant stock/prices and product-specific return/refund rules; do not automatically put returned dairy back into saleable inventory.
4. Add payment integration, real notifications and a support-ticket workflow.
5. Improve checkout with address validation/map pins, automatic split orders for incompatible fresh/standard carts and rescheduling.
6. Add delivery confirmation OTP/proof, route-based ETA and reliable rider mobile tracking.
7. Add purchase-verified reviews, improved search suggestions, typo handling and relevant recommendations.
8. Expand reports to delivered revenue, refunds, margins, expiry wastage, rider performance and delivery lateness.
9. Upgrade the stack and add deployment automation, backups, monitoring and performance testing before real customers use it.
10. Consider multi-seller accounts/commissions only if a marketplace is actually needed; the present architecture is a single-store application.

Start with correctness and fulfillment operations. Adding more visible features before resolving the first four high-priority findings would make the current inconsistencies harder to manage.
