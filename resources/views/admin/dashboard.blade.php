@extends('layouts.admin')@section('admin_title', 'Dashboard')@section('admin_content')
<div class="admin-welcome">
    <div>
        <h2>Your store, at a glance.</h2>
        <p>Manage your catalog, track orders, and keep things moving.</p>
    </div>@if(auth()->user()->canManage('products'))<a class="primary" href="{{ route('admin.create', 'products') }}">Add
    product</a>@endif
</div>
<div class="stat-grid">
    @foreach([['Order value', $money($orderValue), 'Test orders · excluding cancellations'], ['Total orders', $orderCount, $todayCount . ' placed today'], ['Products', $products, $low->count() . ' running low on stock'], ['Customers', $users, 'Registered accounts']] as $stat)
        <article class="stat">
            <div>{{ $stat[0] }}</div><strong>{{ $stat[1] }}</strong><small>{{ $stat[2] }}</small>
    </article>@endforeach
</div>
<div class="dashboard-grid">
    <section class="admin-card">
        <div class="section-head">
            <h3>Sales overview</h3><a class="text-link" href="{{ route('admin.index', 'reports') }}">Reports</a>
        </div>
        <div class="chart">@php($max = max(100, $sales->max('total')))@foreach($sales->take(-7) as $day)
            <div><span style="height:{{ max(3, $day->total / $max * 170) }}px"
                    title="{{ $money($day->total) }}"></span><small>{{ date('d M', strtotime($day->day)) }}</small></div>
        @endforeach
        </div>
        <div class="chart-caption">
            {{ $sales->count() ? 'Saved test order value' : 'Your first order will bring this chart to life.' }}</div>
    </section>
    <section class="admin-card">
        <h3>Order status</h3>
        <div class="status-summary">
            @foreach(['Placed', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled', 'Return requested'] as $status)
            <div><span>{{ $status }}</span><strong>{{ $statuses[$status] ?? 0 }}</strong></div>@endforeach
        </div>
    </section>
</div>
<div class="dashboard-grid">
    <section class="admin-card">
        <div class="section-head">
            <h3>Recent orders</h3><a class="text-link" href="{{ route('admin.index', 'orders') }}">View all</a>
        </div>@forelse($recent as $o)
            <div class="list-row"><a
                    href="{{ route('orders.show', $o) }}"><strong>{{ $o->number }}</strong><small>{{ $o->address['name'] ?? '' }}</small></a><span
        class="status">{{ $o->status }}</span><strong>{{ $money($o->total) }}</strong></div>@empty<div
                    class="mini-empty">
                    <p>No orders yet</p><a class="text-link" href="/shop">Place your first test order</a>
                </div>@endforelse
    </section>
    <section class="admin-card">
        <h3>Inventory watch</h3>@forelse($low as $p)
            <div class="list-row"><span>{{ $p->name }}</span><strong class="danger">{{ $p->stock }} left</strong></div>
        @empty<div class="mini-empty">
            <p>Your stock levels look good.</p><small>Products with 5 or fewer units appear here.</small>
        </div>@endforelse
    </section>
</div>@endsection