from pathlib import Path

content = r'''@extends('layouts.admin')

@section('admin_title', 'New Outward')

@section('admin_content')

<style>
    .new-outward-page {
        width: 100%;
    }

    .new-outward-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .new-outward-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .new-outward-icon {
        width: 48px;
        height: 48px;
        border-radius: 11px;
        background: #153f45;
        color: #d9f25d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .new-outward-title h2 {
        margin: 0 0 4px;
        font-size: 25px;
        color: #111827;
    }

    .new-outward-title p {
        margin: 0;
        color: #718096;
        font-size: 13px;
    }

    .back-outward {
        text-decoration: none;
        color: #153f45;
        font-size: 14px;
        font-weight: 600;
    }

    .new-outward-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 20px;
        align-items: start;
    }

    .outward-card {
        background: #fff;
        border: 1px solid #e1e6e7;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 18px;
    }

    .outward-card:last-child {
        margin-bottom: 0;
    }

    .outward-card-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #edf0f1;
    }

    .outward-card-heading h3 {
        margin: 0;
        font-size: 16px;
        color: #172022;
    }

    .outward-card-heading p {
        margin: 4px 0 0;
        color: #8a9698;
        font-size: 12px;
    }

    .outward-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .outward-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .outward-field.full {
        grid-column: 1 / -1;
    }

    .outward-field label {
        font-size: 12px;
        font-weight: 600;
        color: #4b5a5d;
    }

    .outward-field input,
    .outward-field select,
    .outward-field textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #dce3e4;
        border-radius: 8px;
        background: #fff;
        color: #172022;
        font-size: 13px;
        outline: none;
    }

    .outward-field input,
    .outward-field select {
        height: 43px;
        padding: 0 12px;
    }

    .outward-field textarea {
        min-height: 85px;
        padding: 11px 12px;
        resize: vertical;
    }

    .outward-field input:focus,
    .outward-field select:focus,
    .outward-field textarea:focus {
        border-color: #153f45;
        box-shadow: 0 0 0 3px rgba(21, 63, 69, .07);
    }

    .readonly-field {
        background: #f7f9f9 !important;
        color: #667477 !important;
    }

    .field-help {
        color: #94a3b8;
        font-size: 11px;
    }

    .customer-preview {
        margin-top: 16px;
        padding: 15px;
        background: #f7f9f9;
        border: 1px solid #e6ebec;
        border-radius: 9px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 13px;
    }

    .customer-preview-item span {
        display: block;
        color: #8a9698;
        font-size: 11px;
        margin-bottom: 4px;
    }

    .customer-preview-item strong {
        color: #273437;
        font-size: 13px;
    }

    .items-table-wrap {
        overflow-x: auto;
    }

    .items-table {
        width: 100%;
        min-width: 850px;
        border-collapse: collapse;
    }

    .items-table th {
        background: #f7f9f9;
        color: #657376;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        text-align: left;
        padding: 11px 10px;
        white-space: nowrap;
    }

    .items-table td {
        border-top: 1px solid #edf0f1;
        padding: 10px;
        vertical-align: middle;
    }

    .items-table input,
    .items-table select {
        width: 100%;
        height: 38px;
        box-sizing: border-box;
        border: 1px solid #dce3e4;
        border-radius: 7px;
        padding: 0 9px;
        font-size: 12px;
        outline: none;
    }

    .items-table input:focus,
    .items-table select:focus {
        border-color: #153f45;
    }

    .stock-label {
        font-size: 11px;
        color: #718096;
        white-space: nowrap;
    }

    .stock-label strong {
        color: #18794e;
    }

    .line-total {
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }

    .remove-item {
        width: 31px;
        height: 31px;
        border: 1px solid #ead6d6;
        background: #fff;
        color: #c53030;
        border-radius: 7px;
        cursor: pointer;
    }

    .add-product-btn {
        margin-top: 14px;
        height: 39px;
        padding: 0 14px;
        border: 1px dashed #b8c5c7;
        border-radius: 7px;
        background: #fff;
        color: #153f45;
        font-weight: 600;
        cursor: pointer;
    }

    .add-product-btn:hover {
        background: #f7f9f9;
    }

    .summary-list {
        display: flex;
        flex-direction: column;
        gap: 13px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        color: #657376;
        font-size: 13px;
    }

    .summary-row strong {
        color: #172022;
    }

    .summary-total {
        border-top: 1px solid #e1e6e7;
        margin-top: 4px;
        padding-top: 16px;
        font-size: 17px;
        color: #172022;
        font-weight: 700;
    }

    .summary-total strong {
        font-size: 19px;
    }

    .stock-info {
        background: #f1f8ed;
        border: 1px solid #dbead3;
        border-radius: 9px;
        padding: 14px;
        margin-bottom: 16px;
    }

    .stock-info-title {
        font-size: 12px;
        font-weight: 700;
        color: #315b2a;
        margin-bottom: 5px;
    }

    .stock-info-text {
        font-size: 11px;
        color: #60725b;
        line-height: 1.5;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 9px;
        margin-top: 18px;
    }

    .action-buttons button,
    .action-buttons a {
        width: 100%;
        min-height: 42px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-sizing: border-box;
        cursor: pointer;
    }

    .btn-primary {
        border: 0;
        background: #153f45;
        color: #fff;
    }

    .btn-primary:hover {
        background: #0e3035;
    }

    .btn-secondary {
        border: 1px solid #d8e0e1;
        background: #fff;
        color: #153f45;
    }

    .btn-cancel {
        border: 0;
        background: #f6f7f7;
        color: #657376;
    }

    .required {
        color: #c53030;
    }

    @media (max-width: 1050px) {
        .new-outward-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .new-outward-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .outward-form-grid {
            grid-template-columns: 1fr;
        }

        .outward-field.full {
            grid-column: auto;
        }

        .customer-preview {
            grid-template-columns: 1fr;
        }
    }
</style>


<div class="new-outward-page">

    {{-- PAGE HEADER --}}
    <div class="new-outward-header">

        <div class="new-outward-title">

            <div class="new-outward-icon">
                📦
            </div>

            <div>
                <h2>Create Stock Outward</h2>
                <p>Create a new stock dispatch for an order or other stock movement.</p>
            </div>

        </div>

        <a href="{{ url('/admin/outward') }}" class="back-outward">
            ← Back to Outward
        </a>

    </div>


    <div class="new-outward-grid">

        {{-- LEFT COLUMN --}}
        <div>

            {{-- OUTWARD INFORMATION --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Outward Information</h3>
                        <p>Basic information for this stock movement.</p>
                    </div>
                </div>

                <div class="outward-form-grid">

                    <div class="outward-field">
                        <label>Outward Number</label>
                        <input
                            type="text"
                            value="OUT-00025"
                            class="readonly-field"
                            readonly
                        >
                        <span class="field-help">Automatically generated.</span>
                    </div>

                    <div class="outward-field">
                        <label>Outward Date <span class="required">*</span></label>
                        <input
                            type="date"
                            value="{{ date('Y-m-d') }}"
                        >
                    </div>

                    <div class="outward-field">
                        <label>Outward Type <span class="required">*</span></label>
                        <select>
                            <option>Order Dispatch</option>
                            <option>Manual Stock Issue</option>
                            <option>Damaged Stock</option>
                            <option>Expired Stock</option>
                            <option>Internal Transfer</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="outward-field">
                        <label>Warehouse / Store <span class="required">*</span></label>
                        <select>
                            <option>NovaCart Main Store</option>
                            <option>Warehouse 2</option>
                        </select>
                    </div>

                </div>

            </div>


            {{-- ORDER & CUSTOMER --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Order & Customer</h3>
                        <p>Select an order. Customer information can be populated automatically later.</p>
                    </div>
                </div>

                <div class="outward-form-grid">

                    <div class="outward-field full">
                        <label>Order <span class="required">*</span></label>

                        <select>
                            <option>Select an order</option>
                            <option>#ORD-1052 — Om Baviskar — ₹1,165</option>
                            <option>#ORD-1051 — Rahul Patil — ₹850</option>
                            <option>#ORD-1050 — Priya Sharma — ₹450</option>
                        </select>

                    </div>

                </div>

                <div class="customer-preview">

                    <div class="customer-preview-item">
                        <span>Customer</span>
                        <strong>Om Baviskar</strong>
                    </div>

                    <div class="customer-preview-item">
                        <span>Phone</span>
                        <strong>+91 XXXXX XXXXX</strong>
                    </div>

                    <div class="customer-preview-item">
                        <span>Payment Status</span>
                        <strong>Paid</strong>
                    </div>

                    <div class="customer-preview-item">
                        <span>Order Status</span>
                        <strong>Confirmed</strong>
                    </div>

                    <div class="customer-preview-item" style="grid-column: 1 / -1;">
                        <span>Delivery Address</span>
                        <strong>Pune, Maharashtra, India</strong>
                    </div>

                </div>

            </div>


            {{-- PRODUCTS --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Products</h3>
                        <p>Add products and specify the quantity being dispatched.</p>
                    </div>
                </div>

                <div class="items-table-wrap">

                    <table class="items-table">

                        <thead>
                            <tr>
                                <th style="width: 24%;">Product</th>
                                <th style="width: 12%;">SKU</th>
                                <th style="width: 13%;">Available</th>
                                <th style="width: 13%;">Outward Qty</th>
                                <th style="width: 13%;">Unit Price</th>
                                <th style="width: 14%;">Subtotal</th>
                                <th style="width: 6%;"></th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <select>
                                        <option>Rice 5kg</option>
                                        <option>Sugar 1kg</option>
                                        <option>Cooking Oil 1L</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" value="RIC-005" class="readonly-field" readonly>
                                </td>

                                <td>
                                    <span class="stock-label">
                                        <strong>25</strong> units
                                    </span>
                                </td>

                                <td>
                                    <input type="number" value="2" min="1">
                                </td>

                                <td>
                                    <input type="text" value="₹350">
                                </td>

                                <td>
                                    <span class="line-total">₹700.00</span>
                                </td>

                                <td>
                                    <button type="button" class="remove-item" title="Remove">
                                        ×
                                    </button>
                                </td>

                            </tr>

                            <tr>

                                <td>
                                    <select>
                                        <option>Sugar 1kg</option>
                                        <option>Rice 5kg</option>
                                        <option>Cooking Oil 1L</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" value="SUG-001" class="readonly-field" readonly>
                                </td>

                                <td>
                                    <span class="stock-label">
                                        <strong>40</strong> units
                                    </span>
                                </td>

                                <td>
                                    <input type="number" value="1" min="1">
                                </td>

                                <td>
                                    <input type="text" value="₹55">
                                </td>

                                <td>
                                    <span class="line-total">₹55.00</span>
                                </td>

                                <td>
                                    <button type="button" class="remove-item" title="Remove">
                                        ×
                                    </button>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

                <button type="button" class="add-product-btn">
                    ＋ Add Product
                </button>

            </div>


            {{-- DISPATCH DETAILS --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Dispatch Details</h3>
                        <p>Enter shipping and delivery information.</p>
                    </div>
                </div>

                <div class="outward-form-grid">

                    <div class="outward-field">
                        <label>Delivery Partner</label>

                        <select>
                            <option>Select delivery partner</option>
                            <option>Delhivery</option>
                            <option>Blue Dart</option>
                            <option>DTDC</option>
                            <option>India Post</option>
                            <option>Self Delivery</option>
                        </select>
                    </div>

                    <div class="outward-field">
                        <label>Tracking Number</label>

                        <input
                            type="text"
                            placeholder="Enter tracking number"
                        >
                    </div>

                    <div class="outward-field">
                        <label>Shipping Method</label>

                        <select>
                            <option>Standard Delivery</option>
                            <option>Express Delivery</option>
                            <option>Same Day Delivery</option>
                            <option>Self Delivery</option>
                        </select>
                    </div>

                    <div class="outward-field">
                        <label>Expected Delivery</label>

                        <input type="date">
                    </div>

                </div>

            </div>


            {{-- NOTES --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Notes</h3>
                        <p>Optional internal notes about this outward.</p>
                    </div>
                </div>

                <div class="outward-field">

                    <textarea
                        placeholder="Enter any additional notes..."
                    ></textarea>

                </div>

            </div>

        </div>


        {{-- RIGHT COLUMN --}}
        <aside>

            {{-- STOCK INFO --}}
            <div class="stock-info">

                <div class="stock-info-title">
                    Stock validation
                </div>

                <div class="stock-info-text">
                    Available stock will be checked before the outward is confirmed.
                    The final version will prevent quantities greater than available stock.
                </div>

            </div>


            {{-- SUMMARY --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Outward Summary</h3>
                        <p>Estimated transaction value.</p>
                    </div>
                </div>

                <div class="summary-list">

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>₹755.00</strong>
                    </div>

                    <div class="summary-row">
                        <span>Discount</span>
                        <strong>-₹50.00</strong>
                    </div>

                    <div class="summary-row">
                        <span>GST / Tax</span>
                        <strong>₹65.00</strong>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <strong>₹40.00</strong>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <strong>₹810.00</strong>
                    </div>

                </div>

            </div>


            {{-- ACTIONS --}}
            <div class="outward-card">

                <div class="outward-card-heading">
                    <div>
                        <h3>Actions</h3>
                        <p>Save or create this outward.</p>
                    </div>
                </div>

                <div class="action-buttons">

                    <button type="button" class="btn-primary">
                        Create Outward
                    </button>

                    <button type="button" class="btn-secondary">
                        Save Draft
                    </button>

                    <a
                        href="{{ url('/admin/outward') }}"
                        class="btn-cancel"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </aside>

    </div>

</div>

@endsection
'''

path = Path("/mnt/data/outward-create.blade.php")
path.write_text(content, encoding="utf-8")
print(f"Created: {path}")
