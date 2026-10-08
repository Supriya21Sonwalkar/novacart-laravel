# Batch inventory, cancellation and returns

Open http://127.0.0.1:8007/admin and sign in with your existing admin account.

## Receive stock

Use **Inward → Receive stock**. Enter the supplier bill number, receipt date, product, batch number, quantity, purchase cost and expiry. Add another line for another batch. Perishable products require expiry dates. A receipt adds stock atomically and does not change selling prices.

For bulk receipt, download the **batch import template** from Batches & expiry or Inward → Bulk import. The CSV opens in Excel. Keep its 22 headers, use YYYY-MM-DD dates and rupee amounts, then upload CSV or XLSX, validate and confirm. Validation never changes stock. Existing 19-column XLSX imports generate batch numbers; perishable items need the additional expiry columns.

## FIFO and prices

Checkout allocates the oldest received usable batch first, breaking date ties by batch ID. It splits quantities across batches when needed, excludes expired batches and stores the allocation. Order dispatch checks allocated expiry again against the expected delivery date. Dispatch does not deduct stock again.

The product price set under Products is the selling price. Batch purchase cost is internal and is stored separately. Each order item captures its selling price; later catalogue or cost edits do not change that order's invoice. The order invoice number and outward number appear on the dispatch note alongside batch quantities.

## Existing data

Existing physical stock was preserved as opening batches, with unknown purchase costs rather than invented costs. Open a batch number under Batches & expiry to enter actual batch labels, supplier bill, costs and expiry. Receipt ordering and stock quantities are fixed. Correcting details is logged. The alert identifies expired, soon-expiring and older perishable batches missing expiry.

Historic orders lack original batch allocations. They use clearly labelled legacy allocations when cancelled or returned; the original batch cannot be reconstructed from data that was never recorded.

## Cancellation

Customers may cancel before shipping. Cancellation releases the original allocated quantities once, including expired goods back to physical stock; expired goods remain blocked from sale. Shipped or delivered orders must use returns. Cancelling an outward draft alone leaves the customer order unchanged.

## Returns

On a delivered customer's order, use **Return items**, enter quantities and a reason within seven days. Admin **Returns & refunds** supports approval or rejection, physical receipt and inspection, and test refund recording. Inspect each item and enter its usable quantity; unusable units do not re-enter inventory. Expired batches cannot be restocked as usable goods. Partial returns restore only inspected quantities to original batches.

Refunds use original selling prices with the allocated order discount and tax; shipping is excluded. This demo records refunds only; no payment gateway or bank transaction occurs. Repeat submissions cannot restore or refund a return twice.

## Check the flow

1. Receive two batches of a test product with different receipt dates and purchase costs.
2. Order enough units to cross from the oldest batch into the next. Check the outward batch allocation and invoice price.
3. Cancel a separate unshipped order and confirm only its original batches regain stock.
4. Deliver the first order, request a partial return, approve it, inspect some units as usable and some as damaged, then record the test refund.
5. Search by supplier invoice, batch, product or category under Batches & expiry; search customer invoice numbers under Orders or workspace search.

Regression checks use an isolated SQLite database. The local MySQL migration preserves existing products and orders.
