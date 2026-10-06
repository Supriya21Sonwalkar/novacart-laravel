@extends('layouts.admin')@section('admin_title', $config['title'])@section('admin_content')
<div class="admin-toolbar">
    <form class="admin-search" method="get"><input name="q" aria-label="Search records" value="{{ request('q') }}"
            placeholder="Search {{ strtolower($config['title']) }}">@if($module === 'orders')<select name="status">
                <option value="">All statuses</option>
                @foreach(['Placed', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled', 'Return requested', 'Refunded'] as $status)
                <option {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach
            </select>@endif<button>Search</button></form><a class="outline"
        href="{{ route('admin.export', $module) }}">Export
        CSV</a>@if(empty($config['readonly']) && !in_array($module, ['reviews', 'settings', 'shipments']))<a class="primary"
            href="{{ route('admin.create', $module) }}">Add
        {{ $module === 'products' ? 'product' : ($module === 'inventory' ? 'stock adjustment' : 'record') }}</a>@endif
</div>@if($module === 'inventory')
    <div class="inventory-strip">@foreach($stock as $p)<a
        href="{{ route('admin.create', ['module' => 'inventory', 'product_id' => $p->id]) }}"><span>{{ $p->name }}</span><strong
class="{{ $p->stock <= 5 ? 'danger' : '' }}">{{ $p->stock }} in stock</strong></a>@endforeach</div>@endif
@if($module === 'payments')
<p class="notice">These are test transactions. Payment methods can be configured under Settings.</p>@endif
@if($module === 'users')
<p class="muted small">Add accounts or manage roles and activation for registered customers.</p>@endif
<div class="table-scroll">
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
                @elseif($module === 'orders')<strong>{{ $row->number }}</strong><small
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
                            name="status">@foreach(['Placed', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled', 'Return requested', 'Refunded'] as $s)
                            <option {{ $row->status === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
                </select><button>Update</button></form>@else<span
                        class="status">{{ isset($row->active) ? ($row->active ? 'Active' : 'Inactive') : ($row->status ?? (isset($row->approved) ? ($row->approved ? 'Approved' : 'Pending') : 'Saved')) }}</span>@endif
                </td>
                <td>
                    <div class="actions">@if($module === 'orders')<a class="text-link"
                        href="{{ route('orders.show', $row) }}">Details /
                    invoice</a>@elseif(empty($config['readonly']) && $module !== 'inventory')<a class="text-link"
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
    <div class="table-empty">No records yet.</div>@endif
</div>{{ $rows->links() }}@endsection