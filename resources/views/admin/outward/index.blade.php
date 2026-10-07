@extends('layouts.admin')

@section('admin_title', 'Outward')

@section('admin_content')

<style>
    .outward-page {
        width: 100%;
    }

    /* Header */
    .outward-hero {
        background: linear-gradient(135deg, #153f45, #102f34);
        border-radius: 14px;
        padding: 28px 30px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .outward-hero-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .outward-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: rgba(255,255,255,.14);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 25px;
    }

    .outward-hero h2 {
        margin: 0 0 5px;
        font-size: 25px;
        font-weight: 700;
    }

    .outward-hero p {
        margin: 0;
        opacity: .75;
        font-size: 14px;
    }

    .new-outward-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border-radius: 8px;
        background: #fff;
        color: #153f45;
        font-weight: 700;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        transition: .2s ease;
    }

    .new-outward-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(0,0,0,.15);
    }

    /* Filters */
    .outward-filter-card {
        background: #fff;
        border: 1px solid #e2e7e8;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .outward-filters {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        align-items: center;
    }

    .outward-search {
        position: relative;
    }

    .outward-search span {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: #718096;
    }

    .outward-search input,
    .outward-filters select {
        width: 100%;
        height: 44px;
        border: 1px solid #dfe5e6;
        border-radius: 8px;
        background: #fff;
        padding: 0 13px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
    }

    .outward-search input {
        padding-left: 38px;
    }

    .outward-search input:focus,
    .outward-filters select:focus {
        border-color: #153f45;
        box-shadow: 0 0 0 3px rgba(21,63,69,.08);
    }

    .filter-btn {
        height: 44px;
        padding: 0 18px;
        border: 0;
        border-radius: 8px;
        background: #153f45;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    /* Summary cards */
    .outward-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e2e7e8;
        border-radius: 12px;
        padding: 18px;
    }

    .summary-label {
        font-size: 13px;
        color: #718096;
        margin-bottom: 8px;
    }

    .summary-value {
        font-size: 23px;
        font-weight: 700;
        color: #111827;
    }

    .summary-note {
        font-size: 12px;
        color: #8a9698;
        margin-top: 5px;
    }

    /* Table */
    .outward-table-card {
        background: #fff;
        border: 1px solid #e2e7e8;
        border-radius: 12px;
        overflow: hidden;
    }

    .outward-table-header {
        padding: 18px 20px;
        border-bottom: 1px solid #e7ebec;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .outward-table-header h3 {
        margin: 0;
        font-size: 17px;
    }

    .outward-table-header span {
        font-size: 13px;
        color: #718096;
    }

    .outward-table-wrapper {
        overflow-x: auto;
    }

    .outward-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 950px;
    }

    .outward-table th {
        background: #f7f9f9;
        padding: 13px 18px;
        text-align: left;
        font-size: 11px;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #607174;
        white-space: nowrap;
    }

    .outward-table td {
        padding: 17px 18px;
        border-top: 1px solid #edf0f1;
        vertical-align: middle;
        font-size: 14px;
    }

    .outward-number {
        font-weight: 700;
        color: #153f45;
    }

    .outward-date {
        display: block;
        margin-top: 4px;
        color: #8a9698;
        font-size: 12px;
    }

    .order-number {
        font-weight: 600;
    }

    .customer-name {
        font-weight: 600;
    }

    .customer-location {
        display: block;
        color: #8a9698;
        font-size: 12px;
        margin-top: 3px;
    }

    .item-count {
        font-weight: 600;
    }

    .qty-value {
        font-weight: 700;
    }

    .amount {
        font-weight: 700;
        font-size: 15px;
    }

    /* Status */
    .outward-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-packed {
        background: #fff7df;
        color: #9a6900;
    }

    .status-shipped {
        background: #e7f1ff;
        color: #2563a8;
    }

    .status-delivered {
        background: #e6f7ed;
        color: #18794e;
    }

    .status-cancelled {
        background: #fdecec;
        color: #c53030;
    }

    /* Actions */
    .outward-actions {
        display: flex;
        gap: 7px;
    }

    .outward-action {
        width: 34px;
        height: 34px;
        border-radius: 7px;
        border: 1px solid #dce3e4;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #153f45;
        cursor: pointer;
        transition: .2s ease;
    }

    .outward-action:hover {
        background: #f3f7f7;
    }

    /* Responsive */
    @media (max-width: 1100px) {
        .outward-filters {
            grid-template-columns: 1fr 1fr;
        }

        .outward-search {
            grid-column: 1 / -1;
        }

        .outward-summary {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 700px) {
        .outward-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .outward-filters {
            grid-template-columns: 1fr;
        }

        .outward-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="outward-page">

    {{-- ================= HEADER ================= --}}
    <div class="outward-hero">

        <div class="outward-hero-left">

            <div class="outward-icon">
                📦
            </div>

            <div>
                <h2>Stock Outward</h2>

                <p>
                    Manage products leaving your store and track dispatches.
                </p>
            </div>

        </div>

       <a href="{{ route('admin.outward.create') }}" class="...">
    + New Outward
</a>

    </div>


    {{-- ================= SUMMARY ================= --}}
    <div class="outward-summary">

        <div class="summary-card">
            <div class="summary-label">
                Total Outwards
            </div>

            <div class="summary-value">
                24
            </div>

            <div class="summary-note">
                This month
            </div>
        </div>


        <div class="summary-card">
            <div class="summary-label">
                Items Dispatched
            </div>

            <div class="summary-value">
                86
            </div>

            <div class="summary-note">
                Total units
            </div>
        </div>


        <div class="summary-card">
            <div class="summary-label">
                Pending Dispatch
            </div>

            <div class="summary-value">
                5
            </div>

            <div class="summary-note">
                Awaiting shipment
            </div>
        </div>


        <div class="summary-card">
            <div class="summary-label">
                Outward Value
            </div>

            <div class="summary-value">
                ₹42,850
            </div>

            <div class="summary-note">
                This month
            </div>
        </div>

    </div>


    {{-- ================= FILTERS ================= --}}
    <div class="outward-filter-card">

        <div class="outward-filters">

            <div class="outward-search">

                <span>⌕</span>

                <input
                    type="text"
                    placeholder="Search outward no., order, customer or SKU..."
                >

            </div>


            <select>
                <option value="">All Status</option>
                <option>Pending</option>
                <option>Packed</option>
                <option>Dispatched</option>
                <option>Delivered</option>
                <option>Cancelled</option>
                <option>Returned</option>
            </select>


            <select>
                <option value="">All Types</option>
                <option>Order Dispatch</option>
                <option>Manual Stock Issue</option>
                <option>Damaged Stock</option>
                <option>Expired Stock</option>
                <option>Internal Transfer</option>
            </select>


            <select>
                <option value="">All Dates</option>
                <option>Today</option>
                <option>This Week</option>
                <option>This Month</option>
                <option>Last Month</option>
            </select>


            <button class="filter-btn">
                Filter
            </button>

        </div>

    </div>


    {{-- ================= TABLE ================= --}}
    <div class="outward-table-card">

        <div class="outward-table-header">

            <div>
                <h3>Outward History</h3>

                <span>
                    Recent stock movements from your store
                </span>
            </div>

            <span>
                Showing 5 records
            </span>

        </div>


        <div class="outward-table-wrapper">

            <table class="outward-table">

                <thead>

                    <tr>

                        <th>Outward Details</th>

                        <th>Order</th>

                        <th>Customer</th>

                        <th>Items</th>

                        <th>Total Qty</th>

                        <th>Amount</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                    {{-- ROW 1 --}}
                    <tr>

                        <td>
                            <span class="outward-number">
                                #OUT-00024
                            </span>

                            <span class="outward-date">
                                07 Oct 2026 · 09:32 AM
                            </span>
                        </td>


                        <td>
                            <span class="order-number">
                                #ORD-1052
                            </span>
                        </td>


                        <td>

                            <span class="customer-name">
                                Om Baviskar
                            </span>

                            <span class="customer-location">
                                Pune, Maharashtra
                            </span>

                        </td>


                        <td>
                            <span class="item-count">
                                3 products
                            </span>
                        </td>


                        <td>
                            <span class="qty-value">
                                5
                            </span>
                        </td>


                        <td>
                            <span class="amount">
                                ₹1,165.00
                            </span>
                        </td>


                        <td>

                            <span class="outward-status status-shipped">
                                Shipped
                            </span>

                        </td>


                        <td>

                            <div class="outward-actions">

                                <a
                                    href="#"
                                    class="outward-action"
                                    title="View"
                                >
                                    👁
                                </a>

                                <a
                                    href="#"
                                    class="outward-action"
                                    title="Print"
                                >
                                    🖨
                                </a>

                            </div>

                        </td>

                    </tr>


                    {{-- ROW 2 --}}
                    <tr>

                        <td>

                            <span class="outward-number">
                                #OUT-00023
                            </span>

                            <span class="outward-date">
                                06 Oct 2026 · 04:15 PM
                            </span>

                        </td>


                        <td>
                            <span class="order-number">
                                #ORD-1051
                            </span>
                        </td>


                        <td>

                            <span class="customer-name">
                                Rahul Patil
                            </span>

                            <span class="customer-location">
                                Pune, Maharashtra
                            </span>

                        </td>


                        <td>
                            <span class="item-count">
                                2 products
                            </span>
                        </td>


                        <td>
                            <span class="qty-value">
                                3
                            </span>
                        </td>


                        <td>
                            <span class="amount">
                                ₹850.00
                            </span>
                        </td>


                        <td>

                            <span class="outward-status status-packed">
                                Packed
                            </span>

                        </td>


                        <td>

                            <div class="outward-actions">

                                <a href="#" class="outward-action">
                                    👁
                                </a>

                                <a href="#" class="outward-action">
                                    🖨
                                </a>

                            </div>

                        </td>

                    </tr>


                    {{-- ROW 3 --}}
                    <tr>

                        <td>

                            <span class="outward-number">
                                #OUT-00022
                            </span>

                            <span class="outward-date">
                                06 Oct 2026 · 11:20 AM
                            </span>

                        </td>


                        <td>
                            <span class="order-number">
                                #ORD-1050
                            </span>
                        </td>


                        <td>

                            <span class="customer-name">
                                Priya Sharma
                            </span>

                            <span class="customer-location">
                                Pune, Maharashtra
                            </span>

                        </td>


                        <td>
                            <span class="item-count">
                                1 product
                            </span>
                        </td>


                        <td>
                            <span class="qty-value">
                                1
                            </span>
                        </td>


                        <td>
                            <span class="amount">
                                ₹450.00
                            </span>
                        </td>


                        <td>

                            <span class="outward-status status-delivered">
                                Delivered
                            </span>

                        </td>


                        <td>

                            <div class="outward-actions">

                                <a href="#" class="outward-action">
                                    👁
                                </a>

                                <a href="#" class="outward-action">
                                    🖨
                                </a>

                            </div>

                        </td>

                    </tr>


                    {{-- ROW 4 --}}
                    <tr>

                        <td>

                            <span class="outward-number">
                                #OUT-00021
                            </span>

                            <span class="outward-date">
                                05 Oct 2026 · 02:45 PM
                            </span>

                        </td>


                        <td>
                            <span class="order-number">
                                #ORD-1049
                            </span>
                        </td>


                        <td>

                            <span class="customer-name">
                                Sneha Kulkarni
                            </span>

                            <span class="customer-location">
                                Pune, Maharashtra
                            </span>

                        </td>


                        <td>
                            <span class="item-count">
                                4 products
                            </span>
                        </td>


                        <td>
                            <span class="qty-value">
                                8
                            </span>
                        </td>


                        <td>
                            <span class="amount">
                                ₹2,340.00
                            </span>
                        </td>


                        <td>

                            <span class="outward-status status-shipped">
                                Dispatched
                            </span>

                        </td>


                        <td>

                            <div class="outward-actions">

                                <a href="#" class="outward-action">
                                    👁
                                </a>

                                <a href="#" class="outward-action">
                                    🖨
                                </a>

                            </div>

                        </td>

                    </tr>


                    {{-- ROW 5 --}}
                    <tr>

                        <td>

                            <span class="outward-number">
                                #OUT-00020
                            </span>

                            <span class="outward-date">
                                04 Oct 2026 · 10:05 AM
                            </span>

                        </td>


                        <td>
                            <span class="order-number">
                                #ORD-1048
                            </span>
                        </td>


                        <td>

                            <span class="customer-name">
                                Amit Joshi
                            </span>

                            <span class="customer-location">
                                Pimpri, Maharashtra
                            </span>

                        </td>


                        <td>
                            <span class="item-count">
                                2 products
                            </span>
                        </td>


                        <td>
                            <span class="qty-value">
                                2
                            </span>
                        </td>


                        <td>
                            <span class="amount">
                                ₹699.00
                            </span>

                        </td>


                        <td>

                            <span class="outward-status status-cancelled">
                                Cancelled
                            </span>

                        </td>


                        <td>

                            <div class="outward-actions">

                                <a href="#" class="outward-action">
                                    👁
                                </a>

                                <a href="#" class="outward-action">
                                    🖨
                                </a>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection