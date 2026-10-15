# NovaCart vendor flow (retired)

The user has chosen a single store-team dashboard instead of a marketplace vendor portal. Use `/admin/login` and see **STORE_TEAM_FLOW.md**. The instructions below document the earlier disabled implementation and are not the current operating workflow.

The vendor workspace uses the same MySQL database and PHP 7.2 application at http://127.0.0.1:8000/vendor. Vendor sign-in is http://127.0.0.1:8000/vendor/login; the existing customer login also redirects vendor accounts to their workspace.

## Set up a real vendor

1. Admin → Users → Add account: choose the **vendor** role and a strong password.
2. Admin → Vendors → Add/Edit: link the vendor user account. One user is linked to one active vendor store. Configure commission percentage if applicable; an unconfigured rate charges no commission.
3. Admin → Products → Edit: assign the product to its vendor. Existing unassigned products remain platform-owned. Assigning a product does not disclose older orders: ownership is saved on each new order item at checkout.
4. Vendors can create/edit/deactivate only their own products. New products start with zero stock. Product selling prices remain separate from supplier costs stored on batches.

## Supply purchase → warehouse receipt

1. Admin creates a Purchase List order for the vendor.
2. The vendor sees it under **Orders → Purchase orders from NovaCart**. Accept → Processing → Shipped records the supplier response and carrier details.
3. Admin opens the saved purchase and clicks **Receive against purchase order**. Pending quantities and supplier details load into the existing Inward form.
4. Admin records actually received quantities, supplier invoice, purchase costs, batch numbers and expiry dates. Inward and batch stock increase together. Partial receipts update **Partially Received** and pending quantities; the last receipt changes the purchase to **Received**. Over-receipt rolls back the whole transaction.
5. CSV/Excel inward receipts with a matching PO number also update the purchase quantities. Legacy free-text PO references without a matching order remain standalone receipts.

## Vendor receipt requests

Vendor → Inventory Management → Request a stock receipt creates a pending inspection request and does not add stock. Admin → Vendor stock requests → Receive goods uses the same Inward/BatchStock flow. A reviewed request cannot add stock twice. Rejection changes no stock. Negative, damaged and expired stock movements remain under admin control through the existing inventory/outward modules.

## Customer order → vendor readiness → admin outward

1. Customer checkout reserves goods by deducting available FIFO batches, saving vendor ownership, selling price, commission and proportional discount/tax/shipping shares on order items.
2. Each vendor sees only their own items, allocated totals and notifications. Accept → Processing → Ready for dispatch updates that vendor's fulfilment response, not the entire mixed-vendor order.
3. Vendors can propose carrier/tracking details. The admin sees all responses on the order and outward pages. A single proposal pre-fills the outward form; admin reviews and saves it before dispatch.
4. Admin confirms and packs the existing outward, saves dispatch information and dispatches. Stock is not deducted again. Shipment/customer order/vendor status synchronize on Shipped, Delivered and cancellation.
5. Rejection reports a problem to admin; it does not cancel another vendor's items. Admin resolves cancellations using the existing order workflow.
6. Customer returns use the existing return-request, inspection, original-batch restock and test-refund workflow. Vendors see only returned lines belonging to them. They cannot independently restock or refund goods twice.

## Money and notifications

All current customer payments are test payments. Analytics and pending demo earnings come from saved order items and recorded refunds; taxes and shipping are separate from merchandise earnings. Commission is snapshotted from the admin-configured vendor rate. Supplier invoices show received procurement costs and do not imply supplier payment. There is no real payout gateway or settlement ledger, so payout requests are unavailable and payout history is empty. No bank details are collected.

The notification centre reuses the existing notifications table. Order/review preferences control those updates; stock decisions and platform announcements remain visible. Low-stock alerts use the actual usable batch stock and product threshold. New inventory movements include previous/updated quantities; older ledger entries retain their original data.

## Local demo

Run `& 'C:\xampp\php\php.exe' artisan db:seed --class=DemoVendorSeeder` to create the isolated demo vendor account if needed. Email: `vendor@novacart.test`; default local password: `VendorStore!2026` (override using `DEMO_VENDOR_PASSWORD`). The seeder does not assign platform products or expose historical customer orders. Never use these demo credentials on a public deployment.

Run tests with PHP 7.2. Tests require SQLite `:memory:` and refuse the live MySQL database.
