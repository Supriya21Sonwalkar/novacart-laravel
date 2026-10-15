# NovaCart store-team dashboard

This is your own store's administration workspace, using the existing project and `novacart` database. Open http://127.0.0.1:8000/admin/login and use your existing admin credentials. `/admin` is the unified dashboard.

## Operating sequence

1. Create categories, subcategories, brands and products under Admin. Products belong to the store; there is no seller ownership or commission field.
2. Maintain suppliers under **Suppliers** (the existing `vendors` database table). Suppliers supply stock; they do not sign in to the store dashboard.
3. Create a purchase order when needed. Open **Receive goods** on the purchase order to prefill pending lines in Inward. Partial receipts update the pending quantity; over-receipts are rejected.
4. Receive goods through **Inward** with invoice number, batch number, quantity, purchase cost and expiry dates. Check **Batches & expiry** and Inventory after saving. CSV imports and purchase-linked receipts use the same stock flow.
5. Customers browse, complete their profile and place orders through checkout. Stock is allocated by receipt-date FIFO and deducted once at checkout. Batch numbers are audit references, not the FIFO sort key.
6. Open **Orders to prepare**, select an order, and create/confirm its **Order Dispatch** outward record. Inspect allocated batches, pack, save carrier/tracking or a delivery partner, then dispatch. Dispatch must not deduct already allocated stock again.
7. Track delivery in **Delivery operations** and inspect the customer's order page. Carrier tracking is manually saved unless a provider integration is configured. Rider assignment requires an existing delivery schedule.
8. Cancellation before dispatch restores the original allocated stock once. Receive and inspect returns before restoring usable goods and recording a test refund.

## Dashboard

- Store-wide date-filtered order value, recorded refunds and received purchase value.
- Daily order totals and top products, with their calculation limits displayed.
- Order preparation, dispatch, delivery, low-stock, expiring/expired-stock, purchase and return queues.
- Module links respect staff permissions. Staff without reporting permission cannot see financial figures.
- **My profile & security** edits the signed-in account, with unique email validation and current-password verification. It cannot change roles or grant permissions.

## Retired marketplace functionality

The separate vendor portal is disabled in `config/store_management.php`. Old vendor URLs redirect to the administrator login; vendor POST actions are rejected. New checkout does not generate vendor commissions or fulfilment tasks. Historical seller-related tables and references remain intact to preserve existing records; no database reset or destructive rollback was performed. Suppliers remain available for purchasing and inward receipts.

`tests/Feature/VendorDashboardTest.php.disabled` preserves the earlier marketplace test specification for historical reference. The active store-team tests are in `StoreOwnerDashboardTest.php`; existing checkout, FIFO, cancellation, inward, outward and return tests remain active.

## Integration limits

The strengthened local payment reservation, order workspace, expiry blocking, delivery proof, costs, mail queue and recovery workflow are documented in **FLOW_TEST_GUIDE.md**. Use that guide for the current test sequence.

Payments and refunds remain test records, with no real money moved. Production online payments need a payment provider account, credentials and webhook verification. Automatic courier/GPS tracking needs a provider or rider location service. These integrations cannot be activated with local sample data alone. Dashboard order values are not accounting profit; opening stock without purchase costs and operating expenses must not be treated as zero-cost inventory.
