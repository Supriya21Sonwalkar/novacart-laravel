@extends('layouts.admin')@section('admin_title', $config['title'])@section('admin_content')
@if($errors->any())<div class="aw-panel" role="alert">{{ $errors->first() }}</div>@endif<div class="admin-toolbar">
    <form class="admin-search" method="get"><input name="q" aria-label="Search records" value="{{ request('q') }}"
            placeholder="Search {{ strtolower($config['title']) }}" maxlength="100">@if($module === 'orders')<select name="status">
                <option value="">All statuses</option>
                @foreach(['Placed', 'Confirmed', 'Packing', 'Packed', 'Picked up', 'On the way', 'Shipped', 'Delivered', 'Cancelled', 'Return requested', 'Return received', 'Refunded'] as $status)
                <option {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach
            </select>@endif
@if(in_array($module,['products','inventory'],true))<label>Category<select name="category_id" aria-label="Filter by category"><option value="">All Categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ (string)request('category_id')===(string)$category->id?'selected':'' }}>{{ $category->name }}</option>@endforeach</select></label>@endif
<label>Sort<select name="sort"><option value="newest" {{ request('sort','newest')==='newest'?'selected':'' }}>Newest first</option><option value="oldest" {{ request('sort')==='oldest'?'selected':'' }}>Oldest first</option><option value="name" {{ request('sort')==='name'?'selected':'' }}>Name / reference</option></select></label>
<label>Rows<select name="per_page">@foreach([15,30,50] as $size)<option {{ (int)request('per_page',15)===$size?'selected':'' }}>{{ $size }}</option>@endforeach</select></label>
@if($module==='products')<label>Stock<select name="stock"><option value="">All stock</option><option value="low" {{ request('stock')==='low'?'selected':'' }}>Low stock</option><option value="expiring" {{ request('stock')==='expiring'?'selected':'' }}>Expiring within 7 days</option></select></label>@endif
@if(request('preset'))<input type="hidden" name="preset" value="{{ request('preset') }}">@endif @if(request('unread'))<input type="hidden" name="unread" value="1">@endif
<button>Search</button><a href="{{ route('admin.index',$module) }}">Reset</a></form><a class="outline"
        href="{{ route('admin.export', $module) }}">Export
        CSV</a>@if(empty($config['readonly']) && !in_array($module, ['reviews', 'settings', 'shipments']))<a class="primary"
            href="{{ route('admin.create', $module) }}">Add
        {{ $module === 'products' ? 'product' : ($module === 'inventory' ? 'stock adjustment' : 'record') }}</a>@endif
</div>@if($module === 'inventory')

<style>

    .inventory-page {
        width: 100%;
    }

    .inventory-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .inventory-summary-card {
        background: #fff;
        border: 1px solid #e4e8eb;
        border-radius: 10px;
        padding: 18px 20px;
    }

    .inventory-summary-label {
        color: #6f7b80;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .inventory-summary-value {
        color: #102f35;
        font-size: 27px;
        font-weight: 700;
        line-height: 1.1;
    }

    .inventory-summary-note {
        color: #8a9499;
        font-size: 12px;
        margin-top: 7px;
    }

    .inventory-summary-card.low-stock
    .inventory-summary-value {
        color: #c2410c;
    }

    .inventory-section-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 14px;
    }

    .inventory-section-header h3 {
        margin: 0;
        color: #102f35;
        font-size: 20px;
    }

    .inventory-section-header p {
        margin: 5px 0 0;
        color: #7b858b;
        font-size: 13px;
    }

    .inventory-section-count {
        color: #7b858b;
        font-size: 13px;
        white-space: nowrap;
    }

    .inventory-category-panel {
        background: #fff;
        border: 1px solid #e4e8eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .inventory-category-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .inventory-category-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 70px;
        padding: 13px 15px;
        border: 1px solid #e5e9ec;
        border-radius: 9px;
        background: #fff;
        color: inherit;
        text-decoration: none;
        transition:
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .inventory-category-card:hover {
        border-color: #16434a;
        box-shadow: 0 4px 12px rgba(16, 47, 53, .07);
        text-decoration: none;
    }

    .inventory-category-card.active {
        border-color: #16434a;
        background: #f5f9f9;
    }

    .inventory-category-name {
        min-width: 0;
    }

    .inventory-category-name strong {
        display: block;
        color: #173c43;
        font-size: 14px;
        line-height: 1.35;
    }

    .inventory-category-name small {
        display: block;
        margin-top: 4px;
        color: #899298;
        font-size: 12px;
    }

    .inventory-category-count {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: #f1f7f6;
        color: #16434a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .inventory-current-stock {
        margin-bottom: 34px;
    }

    .inventory-category-group {
        margin-bottom: 24px;
    }

    .inventory-category-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding-bottom: 9px;
        margin-bottom: 10px;
        border-bottom: 1px solid #e6eaec;
    }

    .inventory-category-heading h4 {
        margin: 0;
        color: #173c43;
        font-size: 15px;
        font-weight: 700;
    }

    .inventory-category-heading span {
        color: #899298;
        font-size: 12px;
    }

    .inventory-page .inventory-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .inventory-page .inventory-strip > a {
        min-width: 0;
        min-height: 76px;
        box-sizing: border-box;
        padding: 13px 15px;
        background: #fff;
        border: 1px solid #e3e8ea;
        border-radius: 9px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 7px;
        text-decoration: none;
        color: inherit;
        transition:
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .inventory-page .inventory-strip > a:hover {
        border-color: #16434a;
        box-shadow: 0 4px 12px rgba(16, 47, 53, .07);
        text-decoration: none;
    }

    .inventory-page .inventory-strip > a > span {
        display: block;
        color: #173c43;
        font-size: 13px;
        line-height: 1.35;
    }

    .inventory-page .inventory-strip > a > strong {
        display: block;
        color: #162f35;
        font-size: 13px;
    }

    .inventory-page .inventory-strip > a > strong.danger {
        color: #c2410c;
    }

    @media (max-width: 1100px) {

        .inventory-category-grid {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }

        .inventory-page .inventory-strip {
            grid-template-columns: repeat(
                3,
                minmax(0, 1fr)
            );
        }

    }

    @media (max-width: 760px) {

        .inventory-summary {
            grid-template-columns: 1fr;
        }

        .inventory-category-grid {
            grid-template-columns: 1fr;
        }

        .inventory-page .inventory-strip {
            grid-template-columns: repeat(
                2,
                minmax(0, 1fr)
            );
        }

    }

    @media (max-width: 520px) {

        .inventory-page .inventory-strip {
            grid-template-columns: 1fr;
        }

    }

</style>


<div class="inventory-page">

    {{-- =====================================================
         INVENTORY SUMMARY
         ===================================================== --}}

    @php

        $totalProducts = $stock->count();

        $totalStock = $stock->sum('stock');

        $lowStockCount = $stock
            ->where('stock', '<=', 5)
            ->count();

        $outOfStockCount = $stock
            ->where('stock', '<=', 0)
            ->count();

    @endphp


    <div class="inventory-summary">

        <div class="inventory-summary-card">

            <div class="inventory-summary-label">
                Total Products
            </div>

            <div class="inventory-summary-value">
                {{ $totalProducts }}
            </div>

            <div class="inventory-summary-note">
                Products in current view
            </div>

        </div>


        <div class="inventory-summary-card">

            <div class="inventory-summary-label">
                Total Stock Units
            </div>

            <div class="inventory-summary-value">
                {{ number_format($totalStock) }}
            </div>

            <div class="inventory-summary-note">
                Available units
            </div>

        </div>


        <div
            class="inventory-summary-card
            {{ $lowStockCount > 0 ? 'low-stock' : '' }}"
        >

            <div class="inventory-summary-label">
                Low Stock
            </div>

            <div class="inventory-summary-value">
                {{ $lowStockCount }}
            </div>

            <div class="inventory-summary-note">

                @if($outOfStockCount > 0)

                    {{ $outOfStockCount }} out of stock

                @elseif($lowStockCount > 0)

                    Products at or below 5 units

                @else

                    No low-stock products

                @endif

            </div>

        </div>

    </div>


    {{-- =====================================================
         CATEGORY OVERVIEW
         ===================================================== --}}

    @php

        $visibleCategories = $categories->filter(
            function ($category) use ($stock) {

                return $stock
                    ->where(
                        'category_id',
                        $category->id
                    )
                    ->count() > 0;

            }
        );

    @endphp


    <div class="inventory-category-panel">

        <div class="inventory-section-header">

            <div>

                <h3>
                    Category Overview
                </h3>

                <p>
                    Browse inventory according to product category.
                </p>

            </div>

            <span class="inventory-section-count">

                {{ $visibleCategories->count() }}

                {{
                    $visibleCategories->count() === 1
                        ? 'category'
                        : 'categories'
                }}

            </span>

        </div>


        <div class="inventory-category-grid">

            @foreach($visibleCategories as $category)

                @php

                    $categoryProducts = $stock->where(
                        'category_id',
                        $category->id
                    );

                    $categoryStock =
                        $categoryProducts->sum('stock');

                    $isActiveCategory =
                        (string) request('category_id')
                        ===
                        (string) $category->id;

                @endphp


                <a
                    href="{{ request()->fullUrlWithQuery([
                        'category_id' => $category->id,
                        'q' => request('q')
                    ]) }}"
                    class="
                        inventory-category-card
                        {{ $isActiveCategory ? 'active' : '' }}
                    "
                >

                    <div class="inventory-category-name">

                        <strong>
                            {{ $category->name }}
                        </strong>

                        <small>

                            {{ $categoryProducts->count() }}

                            {{
                                $categoryProducts->count() === 1
                                    ? 'product'
                                    : 'products'
                            }}

                            ·

                            {{ number_format($categoryStock) }}
                            units

                        </small>

                    </div>


                    <div class="inventory-category-count">

                        {{ $categoryProducts->count() }}

                    </div>

                </a>

            @endforeach

        </div>

    </div>


    {{-- =====================================================
         CURRENT STOCK - GROUPED BY CATEGORY
         ===================================================== --}}

    <div class="inventory-current-stock">

        <div class="inventory-section-header">

            <div>

                <h3>
                    Current Stock
                </h3>

                <p>
                    Products currently available in inventory.
                </p>

            </div>

            <span class="inventory-section-count">

                {{ $totalProducts }}

                {{
                    $totalProducts === 1
                        ? 'product'
                        : 'products'
                }}

            </span>

        </div>


        @foreach($categories as $category)

            @php

                $categoryProducts = $stock->where(
                    'category_id',
                    $category->id
                );

            @endphp


            @if($categoryProducts->count())

                <div class="inventory-category-group">

                    <div class="inventory-category-heading">

                        <h4>
                            {{ $category->name }}
                        </h4>

                        <span>

                            {{ $categoryProducts->count() }}

                            {{
                                $categoryProducts->count() === 1
                                    ? 'product'
                                    : 'products'
                            }}

                        </span>

                    </div>


                    <div class="inventory-strip">

                        @foreach($categoryProducts as $p)

                            <a
                                href="{{ route(
                                    'admin.create',
                                    [
                                        'module' => 'inventory',
                                        'product_id' => $p->id
                                    ]
                                ) }}"
                            >

                                <span>
                                    {{ $p->name }}
                                </span>

                                <strong
                                    class="
                                        {{ $p->stock <= 5
                                            ? 'danger'
                                            : '' }}
                                    "
                                >
                                    {{ $p->stock }} in stock
                                </strong>

                            </a>

                        @endforeach

                    </div>

                </div>

            @endif

        @endforeach

    </div>

</div>

@endif


{{-- =========================================================
     EXISTING PAYMENT NOTICE
     ========================================================= --}}

@if($module === 'payments')
<p class="notice">These are test transactions. Payment methods can be configured under Settings.</p>@endif
@if($module === 'users')
<p class="muted small">Add accounts or manage roles and activation for registered customers.</p>@endif
@if($module==='orders'&&auth()->user()->canManage('inventory'))<form id="bulk-pack-form" action="{{ route('admin.workspace.bulk-pack') }}" method="post">@csrf<button class="outline">Mark selected orders packed</button><p class="aw-toolbar-note">Select up to 20 orders with confirmed pending outward entries.</p></form>@endif<div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>{{ $module === 'products' ? 'Product' : 'Record' }}</th>
                <th>Details</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>@foreach($rows as $row)@php($d = isset($row->data) ? $row->data : [])
            <tr>
                <td>@if($module === 'products')
                    <div class="table-product"><img src="{{ $row->image }}"
                            alt=""><span><strong>{{ $row->name }}</strong><small>{{ $row->sku }}</small></span></div>
                @elseif($module === 'orders')@if(auth()->user()->canManage('inventory'))@php($packingEntry=\App\Outward::where('active_order_id',$row->id)->first())@if($packingEntry&&$packingEntry->status==='Pending')<input type="checkbox" name="order_ids[]" value="{{ $row->id }}" form="bulk-pack-form" aria-label="Select {{ $row->number }} for packing">@endif @endif<strong>{{ $row->number }}</strong><small
                    class="block">{{ $row->address['name'] ?? '' }}</small>@elseif($module === 'settings'){{ $d['name'] ?? 'Store settings' }}@else{{ $row->name ?? ($row->title ?? ('Record #' . $row->id)) }}@endif
                </td>
                <td>@if($module === 'products'){{ $money($row->price) }} · {{ $row->stock }} in stock
                @elseif($module === 'orders'){{ $money($row->total) }} · {{ $row->email }}
                    @elseif($module === 'users'){{ $row->email }} ·
                        {{ optional(\App\StoreRecord::in('roles')->find($row->role_id))->name }}
                    @elseif($module === 'reviews'){{ optional($row->product)->name }} · {{ $row->rating }}
                        stars<br>{{ \Illuminate\Support\Str::limit($row->text, 80) }}
                    @elseif($module === 'inventory'){{ optional(\App\Product::withTrashed()->find($row->product_id))->name }}
                        · {{ $row->adjustment }} · {{ $row->reason }} @elseif($module==='payments')Order
                        #{{ $row->order_id }} · {{ $money($row->amount) }} · {{ $row->method }}
                        @elseif($module==='shipments')Order #{{ $row->order_id }} · {{ $row->method }} ·
                        {{ $row->tracking_number ?: 'No tracking number' }}
                    @else{{ \Illuminate\Support\Str::limit(json_encode($d, JSON_UNESCAPED_UNICODE), 110) }}@endif</td>
                <td>@if($module === 'orders')
                    <form method="post" action="{{ route('admin.order.status', $row) }}">@csrf<select
                            name="status">@foreach(array_unique(array_merge([$row->status],['Confirmed','Shipped','Delivered','Cancelled','Refunded'])) as $s)
                            <option {{ $row->status === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
                </select><button>Update</button></form>@else<span
                        class="status">{{ isset($row->active) ? ($row->active ? 'Active' : 'Inactive') : ($row->status ?? (isset($row->approved) ? ($row->approved ? 'Approved' : 'Pending') : 'Saved')) }}</span>@endif
                </td>
                <td>
                    <div class="actions">@if($module === 'orders')<a class="text-link"
                        href="{{ route('orders.show', $row) }}">Details /
                    invoice</a>@if(auth()->user()->canManage('inventory'))@php($outwardEntry=\App\Outward::where('active_order_id',$row->id)->first())@if($outwardEntry)<a class="text-link" href="{{ route('admin.outward.show',$outwardEntry) }}">Outward</a>@elseif(in_array($row->status,['Placed','Confirmed','Packing','Packed'],true))<a class="text-link" href="{{ route('admin.outward.create',['order_id'=>$row->id]) }}">Create outward</a>@endif @endif @elseif(empty($config['readonly']) && $module !== 'inventory')<a class="text-link"
                            href="{{ route('admin.edit', [$module, $row->id]) }}">Edit</a>@endif
                        @if(empty($config['readonly']) && !in_array($module, ['inventory', 'settings', 'shipments']))
                            <form method="post" action="{{ route('admin.delete', [$module, $row->id]) }}"
                                data-confirm="Remove or deactivate this record?">@csrf @method('DELETE')<button
                        class="danger">Remove</button></form>@endif
                    </div>
                </td>
            </tr>@endforeach
        </tbody>
    </table>@if(!$rows->count())
    <div class="table-empty">No matching records. Clear the filters or add a record to get started.</div>@endif
</div>{{ $rows->links() }}@endsection
