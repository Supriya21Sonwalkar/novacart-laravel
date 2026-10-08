@extends('layouts.admin')

@section('admin_title', 'Stock Inward')

@section('admin_content')

<style>
    .inward-page {
        padding: 24px;
        max-width: 1600px;
        margin: 0 auto;
    }

    .inward-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 24px;
    }

    .inward-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .inward-header-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #059669;
        font-size: 23px;
    }

    .inward-header h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        color: #111827;
    }

    .inward-header p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 13px;
    }

    .inward-primary-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 18px;
        border: 0;
        border-radius: 9px;
        background: #059669;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: .2s;
    }

    .inward-primary-btn:hover {
        background: #047857;
        color: #fff;
        transform: translateY(-1px);
    }

    /* Summary Cards */

    .inward-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    .inward-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 13px;
        padding: 18px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        box-shadow: 0 2px 7px rgba(0,0,0,.03);
    }

    .inward-card-label {
        color: #6b7280;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .inward-card-value {
        font-size: 23px;
        font-weight: 700;
        color: #111827;
    }

    .inward-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 19px;
    }

    .icon-green {
        background: #ecfdf5;
        color: #059669;
    }

    .icon-blue {
        background: #eff6ff;
        color: #2563eb;
    }

    .icon-orange {
        background: #fff7ed;
        color: #ea580c;
    }

    .icon-purple {
        background: #faf5ff;
        color: #9333ea;
    }

    /* Main Box */

    .inward-main {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,.03);
    }

    /* Filters */

    .inward-filters {
        padding: 18px;
        border-bottom: 1px solid #e5e7eb;
        background: #fafafa;
    }

    .inward-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }

    .inward-field label {
        display: block;
        margin-bottom: 6px;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
    }

    .inward-field input,
    .inward-field select {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
    }

    .inward-field input:focus,
    .inward-field select:focus {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5,150,105,.10);
    }

    .inward-filter-btn {
        height: 40px;
        padding: 0 16px;
        border: 1px solid #d1d5db;
        background: #fff;
        border-radius: 8px;
        cursor: pointer;
        color: #374151;
        font-weight: 600;
        font-size: 13px;
    }

    .inward-filter-btn:hover {
        background: #f3f4f6;
    }

    /* Table */

    .inward-table-wrapper {
        overflow-x: auto;
    }

    .inward-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1100px;
    }

    .inward-table th {
        background: #f9fafb;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: 13px 15px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .inward-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        color: #374151;
        font-size: 13px;
        vertical-align: middle;
    }

    .inward-table tbody tr:hover {
        background: #fafafa;
    }

    .inward-number {
        color: #059669;
        font-weight: 700;
    }

    .inward-date {
        color: #6b7280;
        font-size: 12px;
    }

    .supplier-name {
        font-weight: 600;
        color: #111827;
    }

    .supplier-contact {
        display: block;
        margin-top: 3px;
        color: #9ca3af;
        font-size: 11px;
    }

    .invoice-number {
        font-family: monospace;
        color: #4b5563;
        background: #f3f4f6;
        padding: 4px 7px;
        border-radius: 5px;
        font-size: 11px;
    }

    .qty-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        padding: 4px 8px;
        border-radius: 6px;
        background: #ecfdf5;
        color: #047857;
        font-weight: 700;
    }

    .warehouse {
        color: #4b5563;
    }

    .amount {
        font-weight: 700;
        color: #111827;
    }

    /* Status */

    .inward-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .inward-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .status-received {
        background: #ecfdf5;
        color: #047857;
    }

    .status-received::before {
        background: #10b981;
    }

    .status-pending {
        background: #fff7ed;
        color: #c2410c;
    }

    .status-pending::before {
        background: #f97316;
    }

    .status-partial {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .status-partial::before {
        background: #3b82f6;
    }

    .status-draft {
        background: #f3f4f6;
        color: #4b5563;
    }

    .status-draft::before {
        background: #9ca3af;
    }

    /* Actions */

    .inward-action {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        background: #fff;
        color: #6b7280;
        text-decoration: none;
        cursor: pointer;
        transition: .2s;
    }

    .inward-action:hover {
        border-color: #059669;
        color: #059669;
        background: #ecfdf5;
    }

    /* Bottom */

    .inward-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 18px;
        color: #6b7280;
        font-size: 12px;
    }

    .inward-pagination {
        display: flex;
        gap: 5px;
    }

    .inward-page-btn {
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 7px;
        cursor: pointer;
        color: #4b5563;
    }

    .inward-page-btn.active {
        background: #059669;
        border-color: #059669;
        color: #fff;
    }

    /* Responsive */

    @media (max-width: 1100px) {
        .inward-summary {
            grid-template-columns: repeat(2, 1fr);
        }

        .inward-filter-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 650px) {
        .inward-page {
            padding: 15px;
        }

        .inward-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .inward-summary {
            grid-template-columns: 1fr;
        }

        .inward-filter-grid {
            grid-template-columns: 1fr;
        }

        .inward-footer {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }
    }
</style>

<div class="inward-page">

    {{-- Header --}}
    <div class="inward-header">

        <div class="inward-header-left">
            <div class="inward-header-icon">
                ↓
            </div>

            <div>
                <h1>Stock Inward</h1>
                <p>Manage incoming stock, purchases and supplier receipts.</p>
            </div>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">

    <a
        href="{{ route('admin.inward.import') }}"
        class="inward-primary-btn"
        style="background:#fff; color:#059669; border:1px solid #059669;"
    >
        <span>↓</span>
        Import Excel
    </a>

    <a
        href="{{ route('admin.inward.create') }}"
        class="inward-primary-btn"
    >
        <span>＋</span>
        New Inward
    </a>

</div>

    </div>


    {{-- Summary Cards --}}
    <div class="inward-summary">

        <div class="inward-card">
            <div>
                <div class="inward-card-label">Total Inwards</div>
<div class="inward-card-value">
    {{ number_format($totalInwards) }}
</div>            </div>

            <div class="inward-card-icon icon-green">
                ↓
            </div>
        </div>


        <div class="inward-card">
            <div>
                <div class="inward-card-label">Items Received</div>
<div class="inward-card-value">
    {{ number_format($itemsReceived) }}
</div>            </div>

            <div class="inward-card-icon icon-blue">
                📦
            </div>
        </div>


        <div class="inward-card">
            <div>
                <div class="inward-card-label">Pending Receipts</div>
<div class="inward-card-value">
    {{ number_format($pendingReceipts) }}
</div>            </div>

            <div class="inward-card-icon icon-orange">
                ⏳
            </div>
        </div>


        <div class="inward-card">
            <div>
                <div class="inward-card-label">Inward Value</div>
<div class="inward-card-value">
    ₹{{ number_format($inwardValue/100, 2) }}
</div>            </div>

            <div class="inward-card-icon icon-purple">
                ₹
            </div>
        </div>

    </div>


    {{-- Main Table Box --}}
    <div class="inward-main">

        {{-- Filters --}}
        <div class="inward-filters">

            <div class="inward-filter-grid">

                <div class="inward-field">
                    <label>Search</label>
                    <input
                        type="text"
                        placeholder="Search inward no., supplier or invoice..."
                    >
                </div>


                <div class="inward-field">
                    <label>Supplier</label>

                    <select>
                        <option value="">All Suppliers</option>
                        <option>ABC Traders</option>
                        <option>XYZ Distributors</option>
                        <option>FreshMart Suppliers</option>
                        <option>Global Wholesale</option>
                    </select>
                </div>


                <div class="inward-field">
                    <label>Type</label>

                    <select>
                        <option value="">All Types</option>
                        <option>Purchase Receipt</option>
                        <option>Purchase Return</option>
                        <option>Supplier Replacement</option>
                        <option>Opening Stock</option>
                        <option>Stock Transfer</option>
                        <option>Other</option>
                    </select>
                </div>


                <div class="inward-field">
                    <label>Status</label>

                    <select>
                        <option value="">All Status</option>
                        <option>Received</option>
                        <option>Pending</option>
                        <option>Partially Received</option>
                        <option>Draft</option>
                        <option>Cancelled</option>
                    </select>
                </div>


                <button type="button" class="inward-filter-btn">
                    Filter
                </button>

            </div>

        </div>


        {{-- Table --}}
        <div class="inward-table-wrapper">

            <table class="inward-table">

                <thead>
                    <tr>
                        <th>Inward No.</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Invoice No.</th>
                        <th>Items</th>
                        <th>Total Qty</th>
                        <th>Warehouse</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>


                <tbody>
                     @forelse($inwards as $inward)

        @php
            $itemCount = $inward->items->count();
            $totalQty = $inward->items->sum('received_qty');

            $statusClass = 'status-draft';

            if ($inward->status === 'Received') {
                $statusClass = 'status-received';
            } elseif ($inward->status === 'Pending') {
                $statusClass = 'status-pending';
            } elseif ($inward->status === 'Partially Received') {
                $statusClass = 'status-partial';
            }
        @endphp

        <tr>

            <td>
                <span class="inward-number">
                    {{ $inward->number }}
                </span>
            </td>

            <td>
                <span class="inward-date">
                    {{ $inward->inward_date ? $inward->inward_date->format('d M Y') : '-' }}<br>
                    {{ $inward->created_at ? $inward->created_at->format('h:i A') : '-' }}
                </span>
            </td>

            <td>
                <span class="supplier-name">
                    {{ $inward->supplier ?: '-' }}
                </span>

                @if($inward->supplier_contact)
                    <span class="supplier-contact">
                        {{ $inward->supplier_contact }}
                    </span>
                @endif
            </td>

            <td>
                @if($inward->supplier_invoice_no)
                    <span class="invoice-number">
                        {{ $inward->supplier_invoice_no }}
                    </span>
                @else
                    -
                @endif
            </td>

            <td>
                {{ $itemCount }}
            </td>

            <td>
                <span class="qty-badge">
                    {{ number_format($totalQty) }}
                </span>
            </td>

            <td>
                <span class="warehouse">
                    {{ $inward->warehouse ?: '-' }}
                </span>
            </td>

            <td>
                <span class="amount">
                    ₹{{ number_format($inward->total/100, 2) }}
                </span>
            </td>

            <td>
                <span class="inward-status {{ $statusClass }}">
                    {{ $inward->status }}
                </span>
            </td>

            <td>
                <a
                    href="{{ route('admin.batches',['inward'=>$inward->id]) }}"
                    class="inward-action"
                    title="View Inward"
                >
                    👁
                </a>
            </td>

        </tr>

    @empty

        <tr>
            <td colspan="10" style="text-align:center; padding:40px; color:#6b7280;">
                No inward records found.
            </td>
        </tr>

    @endforelse

                    

                </tbody>

            </table>

        </div>


<div class="inward-footer">Showing {{ $inwards->firstItem()??0 }}–{{ $inwards->lastItem()??0 }} of {{ $inwards->total() }} receipts {{ $inwards->links() }}</div>
</div></div>@endsection