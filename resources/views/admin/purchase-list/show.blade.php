
@extends('layouts.admin')

@section('admin_title', $order->number)

@section('admin_content')
<link rel="stylesheet" href="{{ asset('purchase-list.css') }}">

<div class="aw-panel">
    <div class="aw-panel-head">
        <h2>Purchase Order Information</h2>
        <a class="outline" href="{{ route('admin.purchase-list') }}">
            Purchase List
        </a>
    </div>

    <dl class="pl-vendor-details">
        <dt>Purchase Order Number</dt>
        <dd>{{ $order->number }}</dd>

        <dt>Order Date</dt>
        <dd>{{ $order->order_date->format('d M Y') }}</dd>

        <dt>Vendor</dt>
        <dd>{{ optional($order->vendor)->name }}</dd>

        <dt>Created By</dt>
        <dd>{{ optional($order->creator)->name }}</dd>

        <dt>Status</dt>
        <dd>{{ $order->status }}</dd>
    </dl>

    <h3>Vendor Information</h3>

    @if($order->vendor)
        @php
            $vendor = $order->vendor;
            $vendorData = is_array($vendor->data)
                ? $vendor->data
                : (json_decode($vendor->data ?? '', true) ?: []);

            $vendorDetails = [
                'Vendor name' => $vendor->name,
                'Vendor code' => $vendor->vendor_code ?? ($vendorData['vendor_code'] ?? null),
                'Contact person' => $vendor->contact_person ?? ($vendorData['contact_person'] ?? null),
                'Phone' => $vendor->phone ?? ($vendorData['phone'] ?? null),
                'Email' => $vendor->email ?? ($vendorData['email'] ?? null),
                'Address' => $vendor->address ?? ($vendorData['address'] ?? null),
                'GST number' => $vendor->gst_number ?? ($vendorData['gst_number'] ?? null),
                'Vendor ID' => $vendor->id,
            ];
        @endphp

        <dl class="pl-vendor-details">
            @foreach($vendorDetails as $label => $value)
                <dt>{{ $label }}</dt>
                <dd>{{ filled($value) ? $value : '—' }}</dd>
            @endforeach
        </dl>
    @endif

    @if($order->notes)
        <h3>Notes</h3>
        <p>{{ $order->notes }}</p>
    @endif
</div>

<section class="aw-panel">
    <h2>Ordered Products</h2>

    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Code</th>
                    <th>Category / Subcategory</th>
                    <th>Existing Stock at Placement</th>
                    <th>Ordered Qty</th>
                    <th>Received Qty</th>
                    <th>Pending Qty</th>
                </tr>
            </thead>

            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td>
                            {{ $item->category_name }} /
                            {{ $item->subcategory_name }}
                        </td>
                        <td>{{ $item->existing_stock }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->received_quantity }}</td>
                        <td>{{ $item->pending_quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p>
        Placing this purchase order has not changed inventory.
        Received quantities update when admin records an Inward receipt against this purchase order;
        supplier shipment alone does not add stock.
    </p>
</section>
@if(in_array($order->status,['Placed','Partially Received'],true)&&$order->supplier_status!=='Rejected'&&auth()->user()->canManage('inventory'))<a class="primary" href="{{ route('admin.inward.create',['purchase_order_id'=>$order->id]) }}">Receive against purchase order</a>@endif<section class="aw-panel"><h3>Vendor response</h3><p>{{ $order->supplier_status }} · {{ $order->supplier_notes }}</p><p>Carrier: {{ $order->carrier?:'Not provided' }} · Tracking: {{ $order->tracking_number?:'Not provided' }} · Expected arrival: {{ $order->expected_at?:'Not provided' }}</p></section>@endsection
