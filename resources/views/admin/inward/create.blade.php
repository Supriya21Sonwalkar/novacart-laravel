@extends('layouts.admin')

@section('admin_title','Receive stock')

@section('admin_content')

<link rel="stylesheet" href="/outward.css">

<style>

    /* Compact date row only; leave the rest of the Inward UI unchanged. */

    .ow .ow-card.inward-date-grid {

        display: flex !important;

        flex-direction: row !important;

        align-items: flex-start !important;

        gap: 16px !important;

        width: 100% !important;

        margin: 16px 0 0 !important;

        padding: 16px !important;

        box-sizing: border-box !important;

    }

    .ow .ow-card.inward-date-grid > div {

        flex: 1 1 0 !important;

        width: 0 !important;

        min-width: 0 !important;

        margin: 0 !important;

    }

    .ow .ow-card.inward-date-grid label {

        display: block !important;

        margin: 0 0 6px !important;

    }

    .ow .ow-card.inward-date-grid input[type="date"] {

        display: block !important;

        width: 100% !important;

        max-width: 100% !important;

        margin: 0 !important;

        box-sizing: border-box !important;

    }

    @media (max-width: 640px) {

        .ow .ow-card.inward-date-grid {

            flex-direction: column !important;

        }

        .ow .ow-card.inward-date-grid > div {

            flex: 1 1 auto !important;

            width: 100% !important;

        }

    }

    /\* Align only Vendor and Purchase Order fields. \*/

    .ow .vendor-field,

    .ow .purchase-order-field {

        display: flex !important;

        flex-direction: column !important;

        align-self: start !important;

        min-width: 0 !important;

        margin: 0 !important;

    }

    .ow .vendor-field > label,

    .ow .purchase-order-field > label {

        display: block !important;

        margin: 0 0 8px !important;

        line-height: 1.4 !important;

    }

    .ow .vendor-field > select,

    .ow .purchase-order-field > select {

        display: block !important;

        width: 100% !important;

        margin: 0 !important;

        box-sizing: border-box !important;

    }

    @media (max-width: 640px) {

        .ow .vendor-field,

        .ow .purchase-order-field {

            width: 100% !important;

        }

    }


    /* Compact, category-filtered manual Product Batch table only. */
    .ow .batch-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        border: 1px solid #dbe4ea;
        border-radius: 10px;
        background: #fff;
    }
    .ow table.batch-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: collapse;
    }
    .ow .batch-table th {
        padding: 11px 9px;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
        white-space: nowrap;
        border-bottom: 1px solid #e2e8f0;
    }
    .ow .batch-table td {
        padding: 8px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }
    .ow .batch-table input,
    .ow .batch-table select {
        width: 100%;
        min-width: 105px;
        box-sizing: border-box;
        padding: 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
    }
    .ow .batch-table .batch-category { min-width: 150px; }
    .ow .batch-table .batch-product { min-width: 190px; }
    .ow .batch-table .batch-date { min-width: 150px; }
    .ow .batch-table .batch-remove {
        padding: 7px 10px;
        white-space: nowrap;
    }

</style>

<div class="ow"><div class="ow-head"><div><a href="{{ route('admin.inward') }}">← Inward receipts</a><h1>Receive stock</h1><p>Record supplier invoices and batches. Purchase costs stay separate from the selling price.</p></div><a class="ow-btn" href="{{ route('admin.inward.import') }}">Bulk import</a></div>@include('admin.outward.errors')

<form method="post" action="{{ route('admin.inward.store') }}">@csrf<div class="ow-card ow-grid">

@foreach(['number'=>'Inward number','supplier_invoice_no'=>'Supplier invoice / bill number','warehouse'=>'Warehouse / store','received_by'=>'Received by'] as $field=>$label)

<div>

    <label for="{{ $field }}">{{ $label }}</label>

    <input

        id="{{ $field }}"

        name="{{ $field }}"

        value="{{ old($field, $field === 'number' ? 'IN-'.strtoupper(\Illuminate\Support\Str::random(10)) : ($field === 'received_by' ? auth()->user()->name : ($field === 'warehouse' ? 'Main inventory' : ''))) }}"

        required

        maxlength="{{ $field === 'number' ? 80 : ($field === 'supplier_invoice_no' ? 120 : 190) }}"

    >

</div>

@endforeach

<div><label for="standalone-supplier">Standalone supplier name</label><input id="standalone-supplier" name="supplier" value="{{ old('supplier') }}" maxlength="190"><small>For receipts without a purchase order.</small></div>
<div class="vendor-field">

    <label for="supplier">Supplier (optional)</label>

<select id="supplier" name="vendor_id">

    <option value="">Standalone receipt — choose products below</option>

    @foreach ($vendors as $vendor)

        <option value="{{ $vendor->id }}" {{ old('vendor_id',optional($selectedPurchase)->vendor_id)==$vendor->id?'selected':'' }}>

            {{ $vendor->name }}

        </option>

    @endforeach

</select>

</div>

<div class="purchase-order-field">

    <label for="purchase_order_id">Purchase Order</label>

    <select id="purchase_order_id" name="purchase_order_id" required disabled>

        <option value="">Select a vendor first</option>

    </select>

</div>

</div>

<div id="purchase-order-summary"

     class="ow-card"

     style="width:100%; max-width:100%; box-sizing:border-box; margin-top:16px;"

     hidden>

    <h3 id="purchase-order-title">Purchase Order Items</h3>

    <p>Select the items you have received and enter their quantities.</p>

    <div id="purchase-order-items" style="width:100%; max-width:100%;"></div>

</div>

<div class="ow-card inward-date-grid" style="display:flex !important; flex-direction:row !important; align-items:flex-start !important; gap:16px !important; width:100% !important; margin:16px 0 0 !important; padding:16px !important; box-sizing:border-box !important;">

    <div style="flex:1 1 0; width:0; min-width:0; margin:0;">

        <label for="inward_date">Received date</label>

        <input id="inward_date" type="date" name="inward_date"

               value="{{ old('inward_date',today()->toDateString()) }}"

               max="{{ today()->toDateString() }}" required>

    </div>

    <div style="flex:1 1 0; width:0; min-width:0; margin:0;">

        <label for="invoice_date">Invoice date</label>

        <input id="invoice_date" type="date" name="invoice_date"

               value="{{ old('invoice_date',today()->toDateString()) }}"

               max="{{ today()->toDateString() }}" required>

    </div>

</div>

<div class="ow-card" id="manual-receipt-batches">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h3 style="margin:0;">Product batch</h3>
            <p style="margin:5px 0 0; color:#64748b;">Choose a category first, then select a product from that category.</p>
        </div>
        <button id="add-receipt-line" type="button" class="ow-btn">Add another batch</button>
    </div>

    <div class="batch-table-wrap">
        <table class="batch-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Product</th>
                    <th>Batch number</th>
                    <th>Received quantity</th>
                    <th>Purchase cost (₹)</th>
                    <th>GST (%)</th>
                    <th>Manufacturing date</th>
                    <th>Expiry date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="receipt-lines">
                @php
                    $oldBatchLines = old('items', $receiptLines);
                    $batchCategories = collect($products)->map(function ($product) {
                        $categoryId = $product->getAttribute('category_id');
                        $categoryName = $product->getAttribute('category_name');
                        if (!$categoryName && method_exists($product, 'category')) {
                            $categoryName = optional($product->category)->name;
                        }
                        if (!$categoryName) {
                            $categoryName = $categoryId ? 'Category ' . $categoryId : 'Uncategorized';
                        }
                        return [
                            'id' => $categoryId ? (string) $categoryId : 'uncategorized',
                            'name' => $categoryName,
                        ];
                    })->unique('id')->values();
                @endphp
                @foreach($oldBatchLines as $index => $line)
                    @php
                        $selectedProduct = collect($products)->firstWhere('id', $line['product_id'] ?? null);
                        $selectedCategoryId = $selectedProduct && $selectedProduct->getAttribute('category_id')
                            ? (string) $selectedProduct->getAttribute('category_id')
                            : 'uncategorized';
                    @endphp
                    <tr class="receipt-line">
                        <td>
                            <select class="batch-category" data-role="category" aria-label="Product category">
                                <option value="">Select category</option>
                                @foreach($batchCategories as $category)
                                    <option value="{{ $category['id'] }}" {{ $selectedCategoryId === $category['id'] ? 'selected' : '' }}>
                                        {{ $category['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="batch-product" data-role="product" name="items[{{ $index }}][product_id]" required>
                                <option value="">Choose product</option>
                                @foreach($products as $p)
                                    @php
                                        $productCategoryId = $p->getAttribute('category_id')
                                            ? (string) $p->getAttribute('category_id')
                                            : 'uncategorized';
                                        $productCategoryName = $p->getAttribute('category_name');
                                        if (!$productCategoryName && method_exists($p, 'category')) {
                                            $productCategoryName = optional($p->category)->name;
                                        }
                                        if (!$productCategoryName) {
                                            $productCategoryName = $p->getAttribute('category_id')
                                                ? 'Category ' . $p->getAttribute('category_id')
                                                : 'Uncategorized';
                                        }
                                    @endphp
                                    <option value="{{ $p->id }}"
                                            data-category-id="{{ $productCategoryId }}"
                                            data-category-name="{{ $productCategoryName }}"
                                            {{ (string)($line['product_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>
                                        {{ $p->name }} · {{ $p->sku }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="items[{{ $index }}][batch_number]" value="{{ $line['batch_number'] ?? '' }}" required maxlength="80" aria-label="Batch number"></td>
                        <td><input type="number" name="items[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? 1 }}" required min="1" max="1000000" aria-label="Received quantity"></td>
                        <td><input type="number" step="0.01" name="items[{{ $index }}][unit_cost]" value="{{ $line['unit_cost'] ?? '' }}" required min="0" max="10000000" aria-label="Purchase cost per unit"></td>
                        <td><input type="number" step="0.01" name="items[{{ $index }}][gst_percent]" value="{{ $line['gst_percent'] ?? 0 }}" min="0" max="100" required aria-label="GST percentage"></td>
                        <td><input class="batch-date" type="date" name="items[{{ $index }}][manufactured_on]" value="{{ $line['manufactured_on'] ?? '' }}" max="{{ today()->toDateString() }}" aria-label="Manufacturing date"></td>
                        <td><input class="batch-date" type="date" name="items[{{ $index }}][expires_on]" value="{{ $line['expires_on'] ?? '' }}" aria-label="Expiry date"></td>
                        <td><button type="button" class="ow-btn batch-remove remove-line">Remove</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="ow-card"><label for="notes">Receipt notes</label><textarea id="notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea><p>One line per product batch. Saving a receipt adds stock and leaves selling prices unchanged.</p><button class="ow-btn ow-primary">Save receipt and add stock</button></div></form></div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var batchBody = document.getElementById('receipt-lines');
    var addBatchButton = document.getElementById('add-receipt-line');

    if (!batchBody || !addBatchButton) {
        return;
    }

    function filterProducts(row, preserveSelection) {
        var categorySelect = row.querySelector('[data-role="category"]');
        var productSelect = row.querySelector('[data-role="product"]');
        if (!categorySelect || !productSelect) return;

        var selectedCategory = categorySelect.value;
        var currentProduct = preserveSelection ? productSelect.value : '';

        Array.prototype.forEach.call(productSelect.options, function (option, index) {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            var matches = selectedCategory && option.getAttribute('data-category-id') === selectedCategory;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        if (currentProduct && Array.prototype.some.call(productSelect.options, function (option) {
            return option.value === currentProduct && !option.disabled;
        })) {
            productSelect.value = currentProduct;
        } else {
            productSelect.value = '';
        }
    }

    function reindexRows() {
        Array.prototype.forEach.call(batchBody.querySelectorAll('tr.receipt-line'), function (row, index) {
            row.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
    }

    batchBody.querySelectorAll('tr.receipt-line').forEach(function (row) {
        filterProducts(row, true);
        var category = row.querySelector('[data-role="category"]');
        if (category) {
            category.addEventListener('change', function () {
                filterProducts(row, false);
            });
        }
    });

    addBatchButton.addEventListener('click', function () {
        var rows = batchBody.querySelectorAll('tr.receipt-line');
        var template = rows.length ? rows[rows.length - 1] : null;
        if (!template) return;

        var newRow = template.cloneNode(true);
        newRow.querySelectorAll('input').forEach(function (input) {
            if (input.type === 'number' && input.name.indexOf('[quantity]') !== -1) {
                input.value = '1';
            } else if (input.name.indexOf('[gst_percent]') !== -1) {
                input.value = '0';
            } else {
                input.value = '';
            }
        });

        var categorySelect = newRow.querySelector('[data-role="category"]');
        var productSelect = newRow.querySelector('[data-role="product"]');
        if (categorySelect) categorySelect.value = '';
        if (productSelect) productSelect.value = '';

        batchBody.appendChild(newRow);
        filterProducts(newRow, false);
        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                filterProducts(newRow, false);
            });
        }
        reindexRows();
    });

    batchBody.addEventListener('click', function (event) {
        var removeButton = event.target.closest('.remove-line');
        if (!removeButton) return;

        var rows = batchBody.querySelectorAll('tr.receipt-line');
        var row = removeButton.closest('tr.receipt-line');

        if (rows.length <= 1) {
            row.querySelectorAll('input').forEach(function (input) {
                if (input.name.indexOf('[quantity]') !== -1) input.value = '1';
                else if (input.name.indexOf('[gst_percent]') !== -1) input.value = '0';
                else input.value = '';
            });
            var category = row.querySelector('[data-role="category"]');
            var product = row.querySelector('[data-role="product"]');
            if (category) category.value = '';
            if (product) product.value = '';
            filterProducts(row, false);
            return;
        }

        row.remove();
        reindexRows();
    });
});
</script>

@endsection

@section('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    var vendorSelect = document.getElementById('supplier');

    var orderSelect = document.getElementById('purchase_order_id');
    var initialPurchase = @json((string)old('purchase_order_id', optional($selectedPurchase)->id));
    var manualBatches = document.getElementById('manual-receipt-batches');
    function receiptMode(){var linked=!!vendorSelect.value;orderSelect.required=linked;document.getElementById('standalone-supplier').required=!linked;if(manualBatches){manualBatches.hidden=linked;manualBatches.querySelectorAll('input,select,button').forEach(function(field){field.disabled=linked;});}}

    var summary = document.getElementById('purchase-order-summary');

    var title = document.getElementById('purchase-order-title');

    var itemsContainer = document.getElementById('purchase-order-items');

    if (!vendorSelect || !orderSelect || !summary || !itemsContainer) {

        console.error('Purchase order form elements are missing.');

        return;

    }

    function createField(labelText, name, type, value, required) {

        var wrapper = document.createElement('div');

        wrapper.style.marginTop = '10px';

        var label = document.createElement('label');

        label.textContent = labelText;

        var input = document.createElement('input');

        input.type = type;

        input.name = name;

        input.style.width = '100%';

        if (value !== undefined && value !== null) {

            input.value = value;

        }

        if (required) {

            input.required = true;

        }

        if (type === 'number') {

            input.min = '0';

            input.step = 'any';

        }

        label.appendChild(input);

        wrapper.appendChild(label);

        return wrapper;

    }

    function createReceivedItem(item) {

        var row = document.createElement('tr');

        var prefix = 'received_items[' + item.id + ']';

        function makeCell() {

            var cell = document.createElement('td');

            cell.style.padding = '10px 8px';

            cell.style.borderBottom = '1px solid #e2e8f0';

            cell.style.verticalAlign = 'middle';

            return cell;

        }

        function makeInput(field, type, value, required) {

            var input = document.createElement('input');

            input.type = type;

            input.name = prefix + '[' + field + ']';

            input.value = value === undefined || value === null ? '' : value;

            input.disabled = true;

            input.required = !!required;

            input.style.width = '100%';

            input.style.minWidth = type === 'date' ? '145px' : '95px';

            input.style.boxSizing = 'border-box';

            input.style.padding = '8px';

            input.style.border = '1px solid #cbd5e1';

            input.style.borderRadius = '6px';

            input.style.fontSize = '13px';

            if (type === 'number') {

                input.min = field === 'quantity' ? '1' : '0';

                input.step = field === 'quantity' ? '1' : 'any';

            }

            return input;

        }

        var selectCell = makeCell();

        selectCell.style.textAlign = 'center';

        var checkbox = document.createElement('input');

        checkbox.type = 'checkbox';

        checkbox.name = prefix + '[selected]';

        checkbox.value = '1';

        checkbox.id = 'received-item-' + item.id;

        checkbox.style.width = '17px';

        checkbox.style.height = '17px';

        checkbox.style.cursor = 'pointer';

        selectCell.appendChild(checkbox);

        row.appendChild(selectCell);

        var productCell = makeCell();

        productCell.style.minWidth = '170px';

        var productName = document.createElement('div');

        productName.textContent = item.name;

        productName.style.fontWeight = '600';

        var sku = document.createElement('div');

        sku.textContent = item.sku || 'No SKU';

        sku.style.fontSize = '12px';

        sku.style.color = '#64748b';

        sku.style.marginTop = '4px';

        productCell.appendChild(productName);

        productCell.appendChild(sku);

        row.appendChild(productCell);

        var detailsCell = makeCell();

        detailsCell.style.minWidth = '155px';

        var details = document.createElement('div');

        details.textContent = 'Ordered: ' + item.ordered_quantity +

            ' | Received: ' + item.received_quantity;

        details.style.fontSize = '12px';

        details.style.color = '#64748b';

        var pending = document.createElement('div');

        pending.textContent = 'Pending: ' + item.pending_quantity;

        pending.style.fontWeight = '600';

        pending.style.color = '#0f766e';

        pending.style.marginTop = '5px';

        detailsCell.appendChild(details);

        detailsCell.appendChild(pending);

        row.appendChild(detailsCell);

        var fieldConfigs = [

            { label: 'Quantity received', field: 'quantity', type: 'number', value: item.pending_quantity, required: true },

            { label: 'Batch number', field: 'batch_number', type: 'text', value: '', required: true },

            { label: 'Unit cost', field: 'unit_cost', type: 'number', value: '0', required: true },

            { label: 'GST (%)', field: 'gst_percent', type: 'number', value: '0', required: true },

            { label: 'Manufactured on', field: 'manufactured_on', type: 'date', value: '', required: false },

            { label: 'Expires on', field: 'expires_on', type: 'date', value: '', required: false }

        ];

        fieldConfigs.forEach(function (config) {

            var cell = makeCell();

            cell.style.minWidth = config.type === 'date' ? '155px' : '125px';

            var label = document.createElement('label');

            label.textContent = config.label;

            label.style.display = 'block';

            label.style.fontSize = '11px';

            label.style.fontWeight = '600';

            label.style.marginBottom = '5px';

            label.style.color = '#475569';

            var input = makeInput(config.field, config.type, config.value, config.required);

            input.id = 'received-' + item.id + '-' + config.field;

            label.htmlFor = input.id;

            if (config.field === 'quantity') {

                input.max = String(item.pending_quantity);

            }

            label.appendChild(input);

            cell.appendChild(label);

            row.appendChild(cell);

        });

        checkbox.addEventListener('change', function () {

            row.querySelectorAll('input:not([type="checkbox"])').forEach(function (input) {

                input.disabled = !checkbox.checked;

            });

            row.style.backgroundColor = checkbox.checked ? '#f0fdfa' : '#ffffff';

        });

        row.style.backgroundColor = '#ffffff';

        return row;

    }

    vendorSelect.addEventListener('change', function () {
        receiptMode();

        orderSelect.innerHTML = '<option value="">Loading orders...</option>';

        orderSelect.disabled = true;

        summary.hidden = true;

        itemsContainer.innerHTML = '';

        if (!vendorSelect.value) {

            orderSelect.innerHTML =

                '<option value="">Select a vendor first</option>';

            return;

        }

        fetch(

            '/admin/purchase-list/vendors/' +

            encodeURIComponent(vendorSelect.value) +

            '/purchase-orders',

            { headers: { Accept: 'application/json' } }

        )

        .then(function (response) {

            if (!response.ok) {

                throw new Error('Could not load purchase orders.');

            }

            return response.json();

        })

        .then(function (orders) {

            orderSelect.innerHTML =

                '<option value="">Select purchase order</option>';

            orders.forEach(function (order) {

                var option = document.createElement('option');

                option.value = order.id;

                option.textContent = order.number + ' (' + order.status + ')';

                orderSelect.appendChild(option);

            });

            orderSelect.disabled = orders.length === 0;
            if(initialPurchase){orderSelect.value=initialPurchase;initialPurchase='';orderSelect.dispatchEvent(new Event('change'));}

            if (orders.length === 0) {

                orderSelect.innerHTML =

                    '<option value="">No pending purchase orders</option>';

            }

        })

        .catch(function (error) {

            orderSelect.innerHTML =

                '<option value="">Unable to load orders</option>';

            alert(error.message);

        });

    });

    orderSelect.addEventListener('change', function () {

        summary.hidden = true;

        itemsContainer.innerHTML = '';

        if (!orderSelect.value) {

            return;

        }

        fetch(

            '/admin/purchase-list/purchase-orders/' +

            encodeURIComponent(orderSelect.value) +

            '/items',

            { headers: { Accept: 'application/json' } }

        )

        .then(function (response) {

            if (!response.ok) {

                throw new Error('Could not load purchase order items.');

            }

            return response.json();

        })

        .then(function (order) {

            title.textContent = 'Purchase Order: ' + order.number;

            var pendingItems = order.items.filter(function (item) {

                return item.pending_quantity > 0;

            });

            if (pendingItems.length === 0) {

                itemsContainer.textContent =

                    'All items in this purchase order have been received.';

            } else {

                var tableWrapper = document.createElement('div');

                tableWrapper.style.width = '100%';

                tableWrapper.style.maxWidth = '100%';

                tableWrapper.style.boxSizing = 'border-box';

                tableWrapper.style.overflowX = 'auto';

                tableWrapper.style.border = '1px solid #e2e8f0';

                tableWrapper.style.borderRadius = '10px';

                var table = document.createElement('table');

                table.style.width = '100%';

                table.style.minWidth = '1250px';

                table.style.borderCollapse = 'collapse';

                table.style.backgroundColor = '#ffffff';

                var thead = document.createElement('thead');

                var headerRow = document.createElement('tr');

                ['Receive', 'Product', 'Order quantities', 'Qty received', 'Batch number', 'Unit cost', 'GST (%)', 'Manufactured', 'Expiry'].forEach(function (heading) {

                    var th = document.createElement('th');

                    th.textContent = heading;

                    th.style.textAlign = 'left';

                    th.style.padding = '12px 8px';

                    th.style.backgroundColor = '#f8fafc';

                    th.style.color = '#475569';

                    th.style.fontSize = '12px';

                    th.style.fontWeight = '700';

                    th.style.borderBottom = '1px solid #e2e8f0';

                    th.style.whiteSpace = 'nowrap';

                    headerRow.appendChild(th);

                });

                thead.appendChild(headerRow);

                var tbody = document.createElement('tbody');

                pendingItems.forEach(function (item) {

                    tbody.appendChild(createReceivedItem(item));

                });

                table.appendChild(thead);

                table.appendChild(tbody);

                tableWrapper.appendChild(table);

                itemsContainer.appendChild(tableWrapper);

            }

            summary.hidden = false;

        })

        .catch(function (error) {

            alert(error.message);

        });

    });

    receiptMode();
    if (vendorSelect.value) {

    vendorSelect.dispatchEvent(new Event('change'));

}

});

</script>

@endsection
