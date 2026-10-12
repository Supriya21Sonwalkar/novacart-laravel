@extends('layouts.admin')
@section('admin_title','Purchase List')
@section('admin_content')
<link rel="stylesheet" href="{{ asset('purchase-list.css') }}">
<div class="aw-panel"><h2>Create purchase order</h2><p>Order goods from a vendor. Stock increases only when goods are received through Inward.</p>
@if(!$vendors->count())<p class="notice">No active vendors are available.@if(auth()->user()->canManage('vendors')) <a href="{{ route('admin.create','vendors') }}">Add a vendor</a>.@endif</p>@endif
<form id="purchase-form" method="post" action="{{ route('admin.purchase-list.store') }}">@csrf
<input type="hidden" name="request_key" value="{{ old('request_key',$requestKey) }}">
<label>Select Vendor<select id="pl-vendor" name="vendor_id" required><option value="">Select Vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" {{ (string)old('vendor_id')===(string)$vendor->id?'selected':'' }}>{{ $vendor->name }}</option>@endforeach</select></label>
<section aria-labelledby="pl-vendor-heading"><h3 id="pl-vendor-heading">Vendor Details</h3><div id="pl-vendor-details" aria-live="polite">Select a vendor to view their details.</div></section>
<h3>Select products</h3><div class="pl-selection">
<label>Category<select id="pl-category"><option value="">Select Category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
<label>Subcategory<select id="pl-subcategory" disabled><option value="">Select Subcategory</option></select></label>
<label>Product<select id="pl-product" disabled><option value="">Select Product</option></select></label>
</div>
<div class="pl-selection"><div><span>Product code</span><strong id="pl-code">—</strong></div><div><span>Existing Stock</span><strong id="pl-stock">—</strong><small>Available stock from the existing batch inventory.</small></div><label>Order Quantity<input id="pl-quantity" type="number" min="1" max="1000000" step="1" value="1"></label></div>
<button id="pl-add" type="button" class="outline" disabled>Add Product</button><p id="pl-message" role="status" aria-live="polite"></p>
<h3>Purchase list</h3><div class="table-scroll"><table class="pl-items"><thead><tr><th>Sr. No.</th><th>Product Name</th><th>Product Code</th><th>Category</th><th>Subcategory</th><th>Existing Stock</th><th>Order Quantity</th><th>Remove</th></tr></thead><tbody id="pl-items"></tbody></table></div><p id="pl-empty">No products added yet.</p>
<label>Notes (optional)<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
<button id="pl-place" class="primary" disabled>PLACE ORDER</button>
</form></div>
<section class="aw-panel"><h2>Saved purchase orders</h2><div class="table-scroll"><table><thead><tr><th>PO number</th><th>Date</th><th>Vendor</th><th>Status</th><th>Created by</th></tr></thead><tbody>@forelse($orders as $order)<tr><td><a href="{{ route('admin.purchase-list.show',$order) }}">{{ $order->number }}</a></td><td>{{ $order->order_date->format('d M Y') }}</td><td>{{ optional($order->vendor)->name }}</td><td>{{ $order->status }}</td><td>{{ optional($order->creator)->name }}</td></tr>@empty<tr><td colspan="5">No purchase orders yet.</td></tr>@endforelse</tbody></table></div>{{ $orders->links() }}</section>
@php
$draft=[];
foreach(is_array(old('items'))?old('items'):[] as $line){
 if(!is_array($line))continue;
 $p=\App\Product::find($line['product_id']??0);if(!$p)continue;
 $category=\App\StoreRecord::in('categories')->find($line['category_id']??0);$sub=\App\StoreRecord::in('categories')->find($line['subcategory_id']??0);
 $draft[]=['id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'stock'=>$p->available_stock,'category_id'=>$category?$category->id:0,'category_name'=>$category?$category->name:'Unavailable','subcategory_id'=>$sub?$sub->id:0,'subcategory_name'=>$sub?$sub->name:'Unavailable','quantity'=>$line['quantity']??''];
}
$purchaseConfig=['vendorUrl'=>url('/admin/purchase-list/vendors'),'subcategoriesUrl'=>route('admin.purchase-list.subcategories'),'productsUrl'=>route('admin.purchase-list.products'),'draft'=>$draft];
@endphp
<script type="application/json" id="pl-config">@json($purchaseConfig)</script>
@endsection
@section('scripts')<script src="{{ asset('purchase-list.js') }}" defer></script>@endsection
