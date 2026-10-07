@extends('layouts.admin')

@section('admin_title', 'New Inward')

@section('admin_content')

<style>
    .new-inward-page {
        padding: 24px;
        max-width: 1500px;
        margin: 0 auto;
    }

    .new-inward-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .new-inward-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .new-inward-back {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        background: #fff;
        color: #374151;
        text-decoration: none;
        font-size: 20px;
        transition: .2s;
    }

    .new-inward-back:hover {
        background: #f3f4f6;
        color: #059669;
    }

    .new-inward-header h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        color: #111827;
    }

    .new-inward-header p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
    }

    .new-inward-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        gap: 20px;
        align-items: start;
    }

    .new-inward-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 13px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 2px 7px rgba(0,0,0,.03);
    }

    .new-inward-section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: #fafafa;
    }

    .new-inward-section-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #ecfdf5;
        color: #059669;
        font-size: 15px;
        font-weight: 700;
    }

    .new-inward-section-header h2 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .new-inward-section-header span {
        margin-left: auto;
        color: #9ca3af;
        font-size: 11px;
    }

    .new-inward-section-body {
        padding: 20px;
    }

    .new-inward-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 17px;
    }

    .new-inward-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 17px;
    }

    .new-inward-field {
        min-width: 0;
    }

    .new-inward-field.full {
        grid-column: 1 / -1;
    }

    .new-inward-field label {
        display: block;
        margin-bottom: 7px;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
    }

    .new-inward-field label .required {
        color: #dc2626;
    }

    .new-inward-field input,
    .new-inward-field select,
    .new-inward-field textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        outline: none;
        transition: .2s;
    }

    .new-inward-field input,
    .new-inward-field select {
        height: 41px;
        padding: 0 12px;
    }

    .new-inward-field textarea {
        min-height: 90px;
        padding: 11px 12px;
        resize: vertical;
    }

    .new-inward-field input:focus,
    .new-inward-field select:focus,
    .new-inward-field textarea:focus {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5,150,105,.10);
    }

    .new-inward-field input[readonly] {
        background: #f9fafb;
        color: #6b7280;
    }

    .new-inward-help {
        display: block;
        margin-top: 5px;
        color: #9ca3af;
        font-size: 10px;
    }

    /* Supplier Preview */

    .supplier-preview {
        margin-top: 18px;
        padding: 14px;
        border: 1px solid #d1fae5;
        background: #f0fdf4;
        border-radius: 10px;
    }

    .supplier-preview-title {
        color: #047857;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .supplier-preview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .supplier-preview-item small {
        display: block;
        color: #6b7280;
        font-size: 10px;
        margin-bottom: 3px;
    }

    .supplier-preview-item strong {
        color: #111827;
        font-size: 12px;
    }

    /* Product Table */

    .product-table-wrapper {
        overflow-x: auto;
    }

    .product-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .product-table th {
        padding: 11px 9px;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
        color: #6b7280;
        font-size: 10px;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .product-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    .product-table input,
    .product-table select {
        width: 100%;
        height: 36px;
        padding: 0 8px;
        box-sizing: border-box;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 12px;
        outline: none;
        background: #fff;
    }

    .product-table input:focus,
    .product-table select:focus {
        border-color: #059669;
    }

    .product-table .product-name {
        min-width: 190px;
    }

    .product-table .sku {
        min-width: 110px;
    }

    .product-table .qty {
        width: 80px;
    }

    .product-table .price {
        width: 105px;
    }

    .product-table .tax {
        width: 75px;
    }

    .product-table .subtotal {
        width: 110px;
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }

    .remove-product {
        width: 32px;
        height: 32px;
        border: 1px solid #fee2e2;
        background: #fff;
        color: #dc2626;
        border-radius: 7px;
        cursor: pointer;
        font-size: 14px;
    }

    .remove-product:hover {
        background: #fef2f2;
    }

    .add-product-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 15px;
        padding: 9px 13px;
        border: 1px dashed #059669;
        background: #f0fdf4;
        color: #047857;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }

    .add-product-btn:hover {
        background: #ecfdf5;
    }

    /* Right Sidebar */

    .inward-sidebar-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 13px;
        margin-bottom: 18px;
        overflow: hidden;
        box-shadow: 0 2px 7px rgba(0,0,0,.03);
    }

    .inward-sidebar-title {
        padding: 15px 17px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        font-weight: 700;
        color: #111827;
    }

    .inward-sidebar-body {
        padding: 17px;
    }

    /* Validation */

    .stock-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 9px;
        color: #1d4ed8;
        font-size: 11px;
        line-height: 1.5;
    }

    .stock-check-icon {
        font-weight: 700;
        font-size: 15px;
    }

    .stock-check strong {
        display: block;
        margin-bottom: 2px;
        font-size: 12px;
    }

    /* Summary */

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        padding: 9px 0;
        color: #6b7280;
        font-size: 12px;
    }

    .summary-row + .summary-row {
        border-top: 1px solid #f3f4f6;
    }

    .summary-row.total {
        margin-top: 7px;
        padding-top: 14px;
        border-top: 1px solid #d1d5db;
        color: #111827;
        font-size: 15px;
        font-weight: 700;
    }

    .summary-value {
        color: #374151;
        font-weight: 600;
    }

    .summary-row.total .summary-value {
        color: #059669;
        font-size: 17px;
    }

    /* Buttons */

    .inward-action-buttons {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .inward-save-btn,
    .inward-draft-btn,
    .inward-cancel-btn {
        width: 100%;
        min-height: 42px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    .inward-save-btn {
        border: 0;
        background: #059669;
        color: #fff;
    }

    .inward-save-btn:hover {
        background: #047857;
    }

    .inward-draft-btn {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .inward-draft-btn:hover {
        background: #f9fafb;
    }

    .inward-cancel-btn {
        border: 0;
        background: transparent;
        color: #6b7280;
    }

    .inward-cancel-btn:hover {
        background: #f9fafb;
        color: #374151;
    }

    /* Info */

    .info-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .info-list li {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        margin-bottom: 10px;
        color: #6b7280;
        font-size: 11px;
        line-height: 1.5;
    }

    .info-list li:last-child {
        margin-bottom: 0;
    }

    .info-list li::before {
        content: "✓";
        color: #059669;
        font-weight: 700;
    }

    /* Responsive */

    @media (max-width: 1100px) {
        .new-inward-layout {
            grid-template-columns: 1fr;
        }

        .new-inward-sidebar {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .new-inward-sidebar .inward-sidebar-card {
            margin-bottom: 0;
        }

        .new-inward-grid-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 700px) {
        .new-inward-page {
            padding: 15px;
        }

        .new-inward-header {
            align-items: flex-start;
        }

        .new-inward-grid,
        .new-inward-grid-4 {
            grid-template-columns: 1fr;
        }

        .supplier-preview-grid {
            grid-template-columns: 1fr 1fr;
        }

        .new-inward-sidebar {
            grid-template-columns: 1fr;
        }
    }
</style>


<div class="new-inward-page">

    {{-- Header --}}
    <div class="new-inward-header">

        <div class="new-inward-header-left">

            <a
                href="{{ url('/admin/inward') }}"
                class="new-inward-back"
                title="Back to Inward"
            >
                ←
            </a>

            <div>
                <h1>Create Stock Inward</h1>
                <p>Record incoming stock received from a supplier or another source.</p>
            </div>

        </div>

    </div>


    <div class="new-inward-layout">

        {{-- ===================================================== --}}
        {{-- LEFT SIDE --}}
        {{-- ===================================================== --}}

        <div>


            {{-- Inward Information --}}
            <div class="new-inward-section">

                <div class="new-inward-section-header">

                    <div class="new-inward-section-icon">
                        01
                    </div>

                    <h2>Inward Information</h2>

                    <span>Basic receiving details</span>

                </div>


                <div class="new-inward-section-body">

                    <div class="new-inward-grid-4">

                        <div class="new-inward-field">

                            <label>
                                Inward Number
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                value="IN-00026"
                                readonly
                            >

                            <span class="new-inward-help">
                                Automatically generated.
                            </span>

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Inward Date
                                <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                value="{{ date('Y-m-d') }}"
                            >

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Inward Type
                                <span class="required">*</span>
                            </label>

                            <select>
                                <option>Purchase Receipt</option>
                                <option>Purchase Return</option>
                                <option>Supplier Replacement</option>
                                <option>Opening Stock</option>
                                <option>Stock Transfer</option>
                                <option>Other</option>
                            </select>

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Warehouse / Store
                                <span class="required">*</span>
                            </label>

                            <select>
                                <option>NovaCart Main Store</option>
                                <option>Warehouse 2</option>
                                <option>Pune Distribution Center</option>
                            </select>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Supplier Information --}}
            <div class="new-inward-section">

                <div class="new-inward-section-header">

                    <div class="new-inward-section-icon">
                        02
                    </div>

                    <h2>Supplier Information</h2>

                    <span>Vendor & invoice details</span>

                </div>


                <div class="new-inward-section-body">

                    <div class="new-inward-grid">

                        <div class="new-inward-field">

                            <label>
                                Supplier
                                <span class="required">*</span>
                            </label>

                            <select>

                                <option value="">
                                    Select Supplier
                                </option>

                                <option selected>
                                    ABC Traders
                                </option>

                                <option>
                                    XYZ Distributors
                                </option>

                                <option>
                                    FreshMart Suppliers
                                </option>

                                <option>
                                    Global Wholesale
                                </option>

                            </select>

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Supplier Contact
                            </label>

                            <input
                                type="text"
                                value="+91 98765 43210"
                                placeholder="Supplier phone number"
                            >

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Supplier Invoice Number
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                value="INV-4585"
                                placeholder="Enter invoice number"
                            >

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Invoice Date
                            </label>

                            <input
                                type="date"
                                value="{{ date('Y-m-d') }}"
                            >

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Purchase Order
                            </label>

                            <select>

                                <option value="">
                                    Select Purchase Order
                                </option>

                                <option>
                                    PO-00158
                                </option>

                                <option>
                                    PO-00157
                                </option>

                                <option>
                                    PO-00156
                                </option>

                            </select>

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Delivery Challan No.
                            </label>

                            <input
                                type="text"
                                placeholder="Optional"
                            >

                        </div>

                    </div>


                    {{-- Supplier Preview --}}
                    <div class="supplier-preview">

                        <div class="supplier-preview-title">
                            Supplier Preview
                        </div>

                        <div class="supplier-preview-grid">

                            <div class="supplier-preview-item">

                                <small>Supplier</small>

                                <strong>
                                    ABC Traders
                                </strong>

                            </div>


                            <div class="supplier-preview-item">

                                <small>Contact</small>

                                <strong>
                                    +91 98765 43210
                                </strong>

                            </div>


                            <div class="supplier-preview-item">

                                <small>GSTIN</small>

                                <strong>
                                    27ABCDE1234F1Z5
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Products --}}
            <div class="new-inward-section">

                <div class="new-inward-section-header">

                    <div class="new-inward-section-icon">
                        03
                    </div>

                    <h2>Products Received</h2>

                    <span>Stock receiving items</span>

                </div>


                <div class="new-inward-section-body">

                    <div class="product-table-wrapper">

                        <table class="product-table">

                            <thead>

                                <tr>

                                    <th>Product</th>

                                    <th>SKU</th>

                                    <th>Ordered Qty</th>

                                    <th>Received Qty</th>

                                    <th>Unit Cost</th>

                                    <th>GST %</th>

                                    <th>Subtotal</th>

                                    <th></th>

                                </tr>

                            </thead>


                            <tbody id="inward-products">

                                {{-- Product 1 --}}
                                <tr>

                                    <td>

                                        <select class="product-name">

                                            <option>
                                                Rice 5kg
                                            </option>

                                            <option>
                                                Wheat Flour 5kg
                                            </option>

                                            <option>
                                                Sugar 1kg
                                            </option>

                                            <option>
                                                Cooking Oil 1L
                                            </option>

                                        </select>

                                    </td>


                                    <td>

                                        <input
                                            type="text"
                                            class="sku"
                                            value="RIC-005"
                                            readonly
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="qty"
                                            value="20"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="qty"
                                            value="20"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="price"
                                            value="320"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="tax"
                                            value="5"
                                            min="0"
                                        >

                                    </td>


                                    <td class="subtotal">
                                        ₹6,400.00
                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="remove-product"
                                            title="Remove Product"
                                        >
                                            ×
                                        </button>

                                    </td>

                                </tr>


                                {{-- Product 2 --}}
                                <tr>

                                    <td>

                                        <select class="product-name">

                                            <option>
                                                Sugar 1kg
                                            </option>

                                            <option>
                                                Rice 5kg
                                            </option>

                                            <option>
                                                Wheat Flour 5kg
                                            </option>

                                            <option>
                                                Cooking Oil 1L
                                            </option>

                                        </select>

                                    </td>


                                    <td>

                                        <input
                                            type="text"
                                            class="sku"
                                            value="SUG-001"
                                            readonly
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="qty"
                                            value="30"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="qty"
                                            value="28"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="price"
                                            value="48"
                                            min="0"
                                        >

                                    </td>


                                    <td>

                                        <input
                                            type="number"
                                            class="tax"
                                            value="5"
                                            min="0"
                                        >

                                    </td>


                                    <td class="subtotal">
                                        ₹1,344.00
                                    </td>


                                    <td>

                                        <button
                                            type="button"
                                            class="remove-product"
                                            title="Remove Product"
                                        >
                                            ×
                                        </button>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <button
                        type="button"
                        class="add-product-btn"
                        id="add-inward-product"
                    >
                        ＋ Add Product
                    </button>

                </div>

            </div>


            {{-- Receiving Details --}}
            <div class="new-inward-section">

                <div class="new-inward-section-header">

                    <div class="new-inward-section-icon">
                        04
                    </div>

                    <h2>Receiving Details</h2>

                    <span>Final receiving information</span>

                </div>


                <div class="new-inward-section-body">

                    <div class="new-inward-grid">

                        <div class="new-inward-field">

                            <label>
                                Received By
                                <span class="required">*</span>
                            </label>

                            <select>

                                <option>
                                    Om Baviskar
                                </option>

                                <option>
                                    Admin
                                </option>

                                <option>
                                    Store Manager
                                </option>

                            </select>

                        </div>


                        <div class="new-inward-field">

                            <label>
                                Receiving Date
                            </label>

                            <input
                                type="date"
                                value="{{ date('Y-m-d') }}"
                            >

                        </div>


                        <div class="new-inward-field full">

                            <label>
                                Notes
                            </label>

                            <textarea
                                placeholder="Add any notes about the received stock..."
                            ></textarea>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- RIGHT SIDE --}}
        {{-- ===================================================== --}}

        <div class="new-inward-sidebar">


            {{-- Stock Validation --}}
            <div class="inward-sidebar-card">

                <div class="inward-sidebar-title">
                    Stock Validation
                </div>

                <div class="inward-sidebar-body">

                    <div class="stock-check">

                        <div class="stock-check-icon">
                            ✓
                        </div>

                        <div>

                            <strong>
                                Stock Ready
                            </strong>

                            Products and received quantities are ready to be added to inventory.

                        </div>

                    </div>

                </div>

            </div>


            {{-- Summary --}}
            <div class="inward-sidebar-card">

                <div class="inward-sidebar-title">
                    Inward Summary
                </div>

                <div class="inward-sidebar-body">

                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <span class="summary-value">
                            2
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Total Qty
                        </span>

                        <span class="summary-value">
                            48
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span class="summary-value">
                            ₹7,744.00
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            GST / Tax
                        </span>

                        <span class="summary-value">
                            ₹387.20
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Other Charges
                        </span>

                        <span class="summary-value">
                            ₹0.00
                        </span>

                    </div>


                    <div class="summary-row total">

                        <span>
                            Grand Total
                        </span>

                        <span class="summary-value">
                            ₹8,131.20
                        </span>

                    </div>

                </div>

            </div>


            {{-- Actions --}}
            <div class="inward-sidebar-card">

                <div class="inward-sidebar-title">
                    Actions
                </div>

                <div class="inward-sidebar-body">

                    <div class="inward-action-buttons">

                        <button
                            type="button"
                            class="inward-save-btn"
                        >
                            ✓ Receive Stock
                        </button>


                        <button
                            type="button"
                            class="inward-draft-btn"
                        >
                            Save as Draft
                        </button>


                        <a
                            href="{{ url('/admin/inward') }}"
                            class="inward-cancel-btn"
                        >
                            Cancel
                        </a>

                    </div>

                </div>

            </div>


            {{-- Information --}}
            <div class="inward-sidebar-card">

                <div class="inward-sidebar-title">
                    What happens next?
                </div>

                <div class="inward-sidebar-body">

                    <ul class="info-list">

                        <li>
                            Received quantities will be added to inventory.
                        </li>

                        <li>
                            Supplier and invoice information will be recorded.
                        </li>

                        <li>
                            Stock movement will be linked to this inward entry.
                        </li>

                        <li>
                            The inward status will change to Received.
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const addButton = document.getElementById('add-inward-product');
    const productTable = document.getElementById('inward-products');

    if (addButton && productTable) {

        addButton.addEventListener('click', function () {

            const row = document.createElement('tr');

            row.innerHTML = `
                <td>
                    <select class="product-name">
                        <option>Rice 5kg</option>
                        <option>Wheat Flour 5kg</option>
                        <option>Sugar 1kg</option>
                        <option>Cooking Oil 1L</option>
                    </select>
                </td>

                <td>
                    <input
                        type="text"
                        class="sku"
                        value="NEW-SKU"
                        readonly
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="qty"
                        value="0"
                        min="0"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="qty"
                        value="0"
                        min="0"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="price"
                        value="0"
                        min="0"
                    >
                </td>

                <td>
                    <input
                        type="number"
                        class="tax"
                        value="0"
                        min="0"
                    >
                </td>

                <td class="subtotal">
                    ₹0.00
                </td>

                <td>
                    <button
                        type="button"
                        class="remove-product"
                        title="Remove Product"
                    >
                        ×
                    </button>
                </td>
            `;

            productTable.appendChild(row);

        });

    }


    document.addEventListener('click', function (event) {

        if (event.target.classList.contains('remove-product')) {

            const row = event.target.closest('tr');

            if (row) {
                row.remove();
            }

        }

    });

});

</script>

@endsection