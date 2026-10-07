@extends('layouts.admin')

@section('admin_title', 'Import Preview')

@section('admin_content')

<style>
    .preview-page {
        padding: 24px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 24px;
    }

    .preview-header h1 {
        margin: 0;
        color: #111827;
        font-size: 25px;
        font-weight: 700;
    }

    .preview-header p {
        margin: 5px 0 0;
        color: #6b7280;
        font-size: 13px;
    }

    .preview-actions {
        display: flex;
        gap: 10px;
    }

    .preview-btn {
        min-height: 40px;
        padding: 0 16px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
    }

    .back-btn {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #374151;
    }

    .back-btn:hover {
        background: #f9fafb;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .summary-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 18px;
    }

    .summary-label {
        color: #6b7280;
        font-size: 11px;
        margin-bottom: 8px;
    }

    .summary-value {
        color: #111827;
        font-size: 25px;
        font-weight: 700;
    }

    .summary-card.success {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .summary-card.success .summary-value {
        color: #15803d;
    }

    .summary-card.error {
        border-color: #fecaca;
        background: #fef2f2;
    }

    .summary-card.error .summary-value {
        color: #dc2626;
    }

    .summary-card.ready {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .summary-card.ready .summary-value {
        color: #2563eb;
        font-size: 18px;
    }

    .notice {
        padding: 14px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 12px;
        line-height: 1.6;
    }

    .notice-warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
        color: #92400e;
    }

    .notice-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .preview-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .preview-card-header {
        padding: 17px 20px;
        border-bottom: 1px solid #e5e7eb;
        background: #fafafa;
    }

    .preview-card-header h2 {
        margin: 0;
        color: #111827;
        font-size: 15px;
        font-weight: 700;
    }

    .preview-card-header p {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 11px;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    .preview-table {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
    }

    .preview-table th {
        padding: 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        color: #475569;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .preview-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 12px;
        white-space: nowrap;
    }

    .preview-table tr:last-child td {
        border-bottom: 0;
    }

    .status {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .status-valid {
        background: #dcfce7;
        color: #166534;
    }

    .status-error {
        background: #fee2e2;
        color: #991b1b;
    }

    .error-row {
        background: #fff7f7;
    }

    .error-list {
        margin: 0;
        padding-left: 18px;
        color: #b91c1c;
        white-space: normal;
    }

    .error-list li {
        margin-bottom: 4px;
    }

    .empty-state {
        padding: 35px;
        text-align: center;
        color: #6b7280;
        font-size: 13px;
    }

    .confirm-area {
        display: flex;
        justify-content: flex-end;
        padding: 18px 20px;
        border-top: 1px solid #e5e7eb;
    }

    .confirm-btn {
        border: 0;
        border-radius: 8px;
        min-height: 41px;
        padding: 0 18px;
        background: #059669;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        cursor: not-allowed;
        opacity: .5;
    }

    @media (max-width: 900px) {
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .preview-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 600px) {
        .preview-page {
            padding: 15px;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>


<div class="preview-page">

    {{-- HEADER --}}
    <div class="preview-header">

        <div>
            <h1>Import Preview</h1>

            <p>
                Review the Excel validation results before importing stock.
            </p>
        </div>

        <div class="preview-actions">

            <a
                href="{{ route('admin.inward.import') }}"
                class="preview-btn back-btn"
            >
                ← Upload Another File
            </a>

        </div>

    </div>


    {{-- SAFETY MESSAGE --}}
    <div class="notice notice-warning">

        <strong>Stock has NOT been changed.</strong>

        This is only the validation and preview stage.
        No inward record, product stock or inventory movement has been created yet.

    </div>


    {{-- SUMMARY --}}
    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Total Excel Rows
            </div>

            <div class="summary-value">
                {{ $preview['total_rows'] }}
            </div>

        </div>


        <div class="summary-card success">

            <div class="summary-label">
                Valid Rows
            </div>

            <div class="summary-value">
                {{ $preview['valid_count'] }}
            </div>

        </div>


        <div class="summary-card error">

            <div class="summary-label">
                Rows With Errors
            </div>

            <div class="summary-value">
                {{ $preview['error_count'] }}
            </div>

        </div>


        <div class="summary-card ready">

            <div class="summary-label">
                Validation Status
            </div>

            <div class="summary-value">

                @if($preview['error_count'] === 0)
                    Ready for Confirmation
                @else
                    Fix Errors First
                @endif

            </div>

        </div>

    </div>


    {{-- VALID ROWS --}}
    <div class="preview-card">

        <div class="preview-card-header">

            <h2>
                Valid Rows
            </h2>

            <p>
                Rows that passed the current validation checks.
            </p>

        </div>


        @if(count($preview['valid_rows']) > 0)

            <div class="table-wrapper">

                <table class="preview-table">

                    <thead>

                        <tr>
                            <th>Excel Row</th>
                            <th>Inward No.</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Supplier</th>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Current Stock</th>
                            <th>Received Qty</th>
                            <th>Unit Cost</th>
                            <th>GST %</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>

                    </thead>


                    <tbody>

                        @foreach($preview['valid_rows'] as $row)

                            <tr>

                                <td>
                                    {{ $row['row_number'] }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $row['inward_no'] }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $row['inward_date'] }}
                                </td>

                                <td>
                                    {{ $row['inward_type'] }}
                                </td>

                                <td>
                                    {{ $row['supplier'] }}
                                </td>

                                <td>
                                    {{ $row['sku'] }}
                                </td>

                                <td>
                                    {{ $row['product_name'] }}
                                </td>

                                <td>
                                    {{ $row['current_stock'] }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $row['received_qty'] }}
                                    </strong>
                                </td>

                                <td>
                                    ₹{{ number_format($row['unit_cost'], 2) }}
                                </td>

                                <td>
                                    {{ $row['gst_percent'] }}%
                                </td>

                                <td>
                                    ₹{{ number_format($row['total'], 2) }}
                                </td>

                                <td>
                                    <span class="status status-valid">
                                        VALID
                                    </span>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-state">
                No valid rows were found.
            </div>

        @endif

    </div>


    {{-- ERROR ROWS --}}
    @if($preview['error_count'] > 0)

        <div class="preview-card">

            <div class="preview-card-header">

                <h2>
                    Rows With Errors
                </h2>

                <p>
                    Fix these rows in your Excel file and upload it again.
                </p>

            </div>


            <div class="table-wrapper">

                <table class="preview-table">

                    <thead>

                        <tr>
                            <th>Excel Row</th>
                            <th>Validation Errors</th>
                            <th>Status</th>
                        </tr>

                    </thead>


                    <tbody>

                        @foreach($preview['errors'] as $error)

                            <tr class="error-row">

                                <td>
                                    <strong>
                                        Row {{ $error['row'] }}
                                    </strong>
                                </td>

                                <td>

                                    <ul class="error-list">

                                        @foreach($error['errors'] as $message)

                                            <li>
                                                {{ $message }}
                                            </li>

                                        @endforeach

                                    </ul>

                                </td>

                                <td>

                                    <span class="status status-error">
                                        ERROR
                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    @else

        <div class="notice notice-success">

            <strong>All rows passed validation.</strong>

            No validation errors were found.

            The final Confirm Import step will be added next.

        </div>

    @endif


    {{-- CONFIRM AREA --}}
    <div class="preview-card">

        <div class="preview-card-header">

            <h2>
                Final Import
            </h2>

            <p>
                Stock will only change after the final confirmation.
            </p>

        </div>

        <div class="confirm-area">

            <button
                type="button"
                class="confirm-btn"
                disabled
            >
                ✓ Confirm Import
            </button>

        </div>

    </div>

</div>

@endsection