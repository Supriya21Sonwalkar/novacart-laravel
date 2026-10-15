
@extends('layouts.admin')

@section('admin_title', 'Delivery Operations')

@section('admin_content')

<style>
    .delivery-page {
        --delivery-ink: #173b40;
        --delivery-muted: #64748b;
        --delivery-border: #e2e8f0;
        --delivery-surface: #ffffff;
        --delivery-bg: #f5f8fa;
        color: #172b35;
        display: flex;
        flex-direction: column;
        gap: 24px;
        padding-bottom: 32px;
    }

    .delivery-page * {
        box-sizing: border-box;
    }

    .delivery-page .delivery-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .delivery-page .delivery-title {
        margin: 0;
        font-size: clamp(24px, 3vw, 32px);
        font-weight: 750;
        letter-spacing: -0.8px;
        color: var(--delivery-ink);
    }

    .delivery-page .delivery-subtitle {
        color: var(--delivery-muted);
        margin: 7px 0 0;
        font-size: 14px;
        line-height: 1.6;
    }

    .delivery-page .delivery-section {
        background: var(--delivery-surface);
        border: 1px solid var(--delivery-border);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(23, 59, 64, 0.035);
        min-width: 0;
    }

    .delivery-page .section-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .delivery-page .section-title {
        color: var(--delivery-ink);
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .delivery-page .section-description {
        color: var(--delivery-muted);
        font-size: 13px;
        line-height: 1.65;
        margin: 6px 0 0;
    }

    .delivery-page .section-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        min-height: 30px;
        padding: 4px 10px;
        border-radius: 9px;
        background: #e8f2f1;
        color: var(--delivery-ink);
        font-size: 12px;
        font-weight: 700;
    }

    .delivery-page .area-grid,
    .delivery-page .partner-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .delivery-page .entity-card {
        border: 1px solid var(--delivery-border);
        border-radius: 13px;
        padding: 18px;
        min-width: 0;
        background: #fff;
    }

    .delivery-page .entity-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 15px;
    }

    .delivery-page .entity-name {
        margin: 0;
        color: var(--delivery-ink);
        font-size: 16px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .delivery-page .entity-meta {
        margin: 5px 0 0;
        color: var(--delivery-muted);
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .delivery-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 30px;
        background: #e9f7ef;
        color: #187447;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .delivery-page .status-pill.inactive {
        background: #f1f5f9;
        color: #64748b;
    }

    .delivery-page .entity-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 17px;
    }

    .delivery-page .detail-label {
        color: var(--delivery-muted);
        display: block;
        font-size: 11px;
        margin-bottom: 5px;
    }

    .delivery-page .detail-value {
        color: #243b47;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .delivery-page .delivery-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .delivery-page .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .delivery-page .field-label {
        display: flex;
        flex-direction: column;
        gap: 7px;
        color: #334b58;
        font-size: 12px;
        font-weight: 650;
        min-width: 0;
    }

    .delivery-page input,
    .delivery-page select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        min-height: 43px;
        border: 1px solid #dbe3e8;
        border-radius: 9px;
        padding: 10px 12px;
        background: #fff;
        color: #243b47;
        font: inherit;
        font-size: 13px;
        outline: none;
        transition: border-color .18s, box-shadow .18s;
    }

    .delivery-page input:focus,
    .delivery-page select:focus {
        border-color: #427e80;
        box-shadow: 0 0 0 3px rgba(66, 126, 128, .12);
    }

    .delivery-page .check-label {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        min-height: 43px;
        color: #334b58;
        font-size: 12px;
        font-weight: 650;
    }

    .delivery-page .check-label input[type="checkbox"] {
        width: auto;
        min-width: 18px;
        min-height: 18px;
        margin: 0;
        padding: 0;
        flex: 0 0 auto;
    }

    .delivery-page select[multiple] {
        min-height: 135px;
        padding: 7px;
    }

    .delivery-page select[multiple] option {
        padding: 7px;
    }

    .delivery-page .primary,
    .delivery-page .secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 41px;
        border-radius: 9px;
        padding: 10px 16px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: background .18s, border-color .18s;
    }

    .delivery-page .primary {
        color: #fff;
        background: var(--delivery-ink);
        border: 1px solid var(--delivery-ink);
    }

    .delivery-page .primary:hover {
        background: #24555b;
        border-color: #24555b;
    }

    .delivery-page .secondary {
        color: var(--delivery-ink);
        background: #fff;
        border: 1px solid var(--delivery-border);
    }

    .delivery-page .secondary:hover {
        background: #f0f7f6;
    }

    .delivery-page .full-width {
        width: 100%;
    }

    .delivery-page .entity-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .delivery-page details > summary {
        cursor: pointer;
        color: var(--delivery-ink);
        font-size: 12px;
        font-weight: 700;
        list-style-position: inside;
        padding: 10px 0 0;
    }

    .delivery-page details[open] > summary {
        margin-bottom: 15px;
    }

    .delivery-page .create-area {
        margin-top: 20px;
        padding-top: 22px;
        border-top: 1px solid var(--delivery-border);
    }

    .delivery-page .table-wrap {
        width: 100%;
        overflow-x: auto;
        border: 1px solid var(--delivery-border);
        border-radius: 12px;
    }

    .delivery-page .delivery-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 12px;
        min-width: 610px;
    }

    .delivery-page .delivery-table th {
        background: #f6f9fa;
        color: #526674;
        padding: 13px 15px;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: 1px solid var(--delivery-border);
    }

    .delivery-page .delivery-table td {
        padding: 14px 15px;
        border-bottom: 1px solid #edf1f4;
        color: #334b58;
        vertical-align: middle;
    }

    .delivery-page .delivery-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .delivery-page .delivery-table tbody tr:hover {
        background: #f9fbfc;
    }

    .delivery-page .time-cell {
        white-space: nowrap;
        font-weight: 650;
        color: var(--delivery-ink);
    }

    .delivery-page .capacity-pill {
        display: inline-flex;
        padding: 5px 9px;
        border-radius: 7px;
        background: #edf5f5;
        color: #28585c;
        font-weight: 700;
        white-space: nowrap;
    }

    .delivery-page .empty-state {
        padding: 28px 18px;
        text-align: center;
        border: 1px dashed #d5dfe5;
        border-radius: 12px;
        background: #fafcfd;
        color: var(--delivery-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .delivery-page .empty-state strong {
        display: block;
        color: var(--delivery-ink);
        margin-bottom: 5px;
        font-size: 14px;
    }

    .delivery-page .partner-form {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .delivery-page .partner-form .primary {
        width: 100%;
    }

    .delivery-page .order-number {
        color: var(--delivery-ink);
        font-weight: 750;
        text-decoration: none;
    }

    .delivery-page .order-number:hover {
        text-decoration: underline;
    }

    .delivery-page .order-status {
        display: inline-block;
        border-radius: 6px;
        background: #f1f5f9;
        padding: 5px 8px;
        color: #526674;
        font-size: 11px;
        font-weight: 700;
    }

    .delivery-page .assign-form {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 300px;
    }

    .delivery-page .assign-form select {
        flex: 1;
    }

    .delivery-page .assign-form .primary {
        flex-shrink: 0;
    }

    .delivery-page .section-divider {
        border: 0;
        border-top: 1px solid var(--delivery-border);
        margin: 0;
    }

    @media (max-width: 850px) {
        .delivery-page .delivery-section {
            padding: 18px;
        }

        .delivery-page .area-grid,
        .delivery-page .partner-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 560px) {
        .delivery-page {
            gap: 16px;
        }

        .delivery-page .delivery-section {
            padding: 15px;
            border-radius: 12px;
        }

        .delivery-page .form-grid,
        .delivery-page .entity-details {
            grid-template-columns: 1fr;
        }

        .delivery-page .assign-form {
            align-items: stretch;
            flex-direction: column;
            min-width: 220px;
        }

        .delivery-page .assign-form .primary {
            width: 100%;
        }

        .delivery-page .entity-actions > * {
            flex: 1;
        }
    }
</style>

<div class="delivery-page">

    {{-- PAGE HEADER --}}
    <header class="delivery-header">
        <div>
            <h1 class="delivery-title">Delivery Operations</h1>
            <p class="delivery-subtitle">
                Manage service coverage, delivery windows, partners and order assignments.
            </p>
        </div>
    </header>

    {{-- =====================================================
         SERVICE AREAS
    ====================================================== --}}
    <section class="delivery-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">Service areas</h2>
                <p class="section-description">
                    Today's delivery windows and available order capacity.
                </p>
            </div>
<span class="section-count">{{ $slots->count() }} slots today</span>        </div>

        @if($zones->count())
            <div class="area-grid">
                @foreach($zones as $z)
                    <article class="entity-card">
                        <div class="entity-top">
                            <div>
                                <h3 class="entity-name">{{ $z->name }}</h3>
                                <p class="entity-meta">{{ $z->city }}</p>
                            </div>

                            <span class="status-pill {{ $z->active ? '' : 'inactive' }}">
                                {{ $z->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="entity-details">
                            <div>
                                <span class="detail-label">Service pincodes</span>
                                <span class="detail-value">{{ $z->pincodes }}</span>
                            </div>
                            <div>
                                <span class="detail-label">Operating hours</span>
                                <span class="detail-value">
                                    {{ \Carbon\Carbon::parse($z->opens_at)->format('h:i A') }}
                                    – {{ \Carbon\Carbon::parse($z->closes_at)->format('h:i A') }}
                                </span>
                            </div>
                            <div>
                                <span class="detail-label">Estimated travel</span>
                                <span class="detail-value">{{ $z->travel_minutes }} minutes</span>
                            </div>
                            <div>
                                <span class="detail-label">Express delivery</span>
                                <span class="detail-value">{{ $z->express ? 'Enabled' : 'Disabled' }}</span>
                            </div>
                        </div>

                        <details>
                            <summary>Edit service area</summary>

                            <form
                                method="post"
                                action="{{ url('/admin/delivery/zones') }}"
                                class="delivery-form"
                            >
                                @csrf
                                <input type="hidden" name="id" value="{{ $z->id }}">
                                @include('delivery.zone-fields', ['z' => $z])

                                <button type="submit" class="primary full-width">
                                    Save changes
                                </button>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <strong>No service areas yet</strong>
                Add a service area below to start configuring delivery coverage.
            </div>
        @endif

        <div class="create-area">
            <div class="section-heading">
                <div>
                    <h3 class="section-title">Add service area</h3>
                    <p class="section-description">
                        Configure a new delivery location.
                    </p>
                </div>
            </div>

            <form
                method="post"
                action="{{ url('/admin/delivery/zones') }}"
                class="delivery-form"
            >
                @csrf
                <div class="form-grid">
                    @include('delivery.zone-fields', ['z' => null])
                </div>

                <button type="submit" class="primary">
                    + Create service area
                </button>
            </form>
        </div>
    </section>

    {{-- =====================================================
         DELIVERY SLOTS
    ====================================================== --}}
    <section class="delivery-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">Delivery schedule</h2>
                <p class="section-description">
                    Create delivery windows and review upcoming slot capacity.
                </p>
            </div>
            <span class="section-count">{{ $slots->count() }} upcoming slots</span>
        </div>

        <details>
            <summary>+ Create a delivery slot</summary>

            <form
                method="post"
                action="{{ url('/admin/delivery/slots') }}"
                class="delivery-form"
            >
                @csrf

                <div class="form-grid">
                    <label class="field-label">
                        Service area
                        <select name="zone_id" required>
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}">
                                    {{ $z->name }} — {{ $z->city }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field-label">
                        Starts
                        <input type="datetime-local" name="starts_at" required>
                    </label>

                    <label class="field-label">
                        Ends
                        <input type="datetime-local" name="ends_at" required>
                    </label>

                    <label class="field-label">
                        Order capacity
                        <input
                            type="number"
                            name="capacity"
                            value="10"
                            min="1"
                            max="1000"
                            required
                        >
                    </label>
                </div>

                <button type="submit" class="primary">
                    Create slot
                </button>
            </form>
        </details>

        <div style="margin-top: 20px;">
            @if($slots->count())
                <div class="table-wrap">
                    <table class="delivery-table">
                        <thead>
                            <tr>
                                <th>Service area</th>
                                <th>Date</th>
                                <th>Start time</th>
                                <th>End time</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($slots as $s)
                                @php
                                    $slotStart = \Carbon\Carbon::parse($s->starts_at);
                                    $slotEnd = \Carbon\Carbon::parse($s->ends_at);
                                    $slotZone = $zones->firstWhere('id', $s->zone_id);
                                @endphp
                                <tr>
                                    <td>
                                        <strong>
                                            {{ $slotZone ? $slotZone->name : 'Area #' . $s->zone_id }}
                                        </strong>
                                        @if($slotZone)
                                            <div class="entity-meta">{{ $slotZone->city }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $slotStart->format('d M Y') }}</td>
                                    <td class="time-cell">{{ $slotStart->format('h:i A') }}</td>
                                    <td class="time-cell">{{ $slotEnd->format('h:i A') }}</td>
                                    <td>
                                        <span class="capacity-pill">
                                            {{ $s->capacity }} orders
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <strong>No upcoming delivery slots</strong>
                    Create a slot to make a delivery window available.
                </div>
            @endif
        </div>
    </section>

    {{-- =====================================================
         DELIVERY PARTNERS
    ====================================================== --}}
    <section class="delivery-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">Delivery partners</h2>
                <p class="section-description">
                    Assign one or more service areas to each active delivery partner.
                </p>
            </div>
            <span class="section-count">{{ $riders->count() }} partners</span>
        </div>

        @if($riders->count())
            <div class="partner-grid">
                @foreach($riders as $rider)
                    @php
                        $assignedZoneIds = isset($riderZoneAssignments[$rider->id])
                            ? $riderZoneAssignments[$rider->id]
                                ->pluck('zone_id')
                                ->map(function ($id) {
                                    return (int) $id;
                                })
                                ->toArray()
                            : [];

                        $assignedZones = $zones->whereIn('id', $assignedZoneIds);
                    @endphp

                    <article class="entity-card">
                        <div class="entity-top">
                            <div>
                                <h3 class="entity-name">{{ $rider->name }}</h3>
                                <p class="entity-meta">{{ $rider->email }}</p>
                            </div>
                            <span class="status-pill">Active</span>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <span class="detail-label">Currently assigned areas</span>
                            @if($assignedZones->count())
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                                    @foreach($assignedZones as $assignedZone)
                                        <span class="capacity-pill">
                                            {{ $assignedZone->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="detail-value">No areas assigned</span>
                            @endif
                        </div>

                        <details>
                            <summary>Manage service areas</summary>

                            <form
                                method="post"
                                action="{{ url('/admin/delivery/riders/' . $rider->id . '/zones') }}"
                                class="partner-form"
                            >
                                @csrf

                                <label class="field-label">
                                    Service areas
                                    <select name="zone_ids[]" multiple size="4">
                                        @foreach($zones as $zone)
                                            <option
                                                value="{{ $zone->id }}"
                                                {{ in_array((int) $zone->id, $assignedZoneIds, true) ? 'selected' : '' }}
                                            >
                                                {{ $zone->name }} — {{ $zone->city }}
                                                ({{ $zone->pincodes }})
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <p class="section-description">
                                    Use Ctrl (Windows) or Command (Mac) to select multiple areas.
                                </p>

                                <button type="submit" class="primary">
                                    Save assigned areas
                                </button>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <strong>No active delivery partners</strong>
                Activate a user with the delivery partner role to manage assignments.
            </div>
        @endif
    </section>

    {{-- =====================================================
         ORDER ASSIGNMENTS
    ====================================================== --}}
    <section class="delivery-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">Order assignments</h2>
                <p class="section-description">
                    Assign eligible delivery partners to scheduled orders.
                </p>
            </div>
            <span class="section-count">{{ $deliveries->count() }} records</span>
        </div>

        @if($deliveries->count())
            <div class="table-wrap">
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Promised delivery</th>
                            <th>Assign delivery partner</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveries as $d)
                            @php
                                $o = \App\Order::find($d->order_id);
                            @endphp

                            @if($o)
                                <tr>
                                    <td>
                                        <a
                                            class="order-number"
                                            href="{{ url('/orders/' . $o->id) }}"
                                        >
                                            {{ $o->number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="order-status">{{ $o->status }}</span>
                                    </td>
                                    <td>
                                        {{ $d->promised_to
                                            ? \Carbon\Carbon::parse($d->promised_to)->format('d M Y, h:i A')
                                            : 'Not scheduled'
                                        }}
                                    </td>
                                    <td>
                                        <form
                                            method="post"
                                            action="{{ url('/admin/delivery/orders/' . $d->order_id . '/assign') }}"
                                            class="assign-form"
                                        >
                                            @csrf

                                            <select name="rider_id" required>
                                                <option value="">
                                                    Choose partner
                                                </option>

                                                @foreach($riders as $r)
                                                    <option
                                                        value="{{ $r->id }}"
                                                        {{ $d->rider_id == $r->id ? 'selected' : '' }}
                                                    >
                                                        {{ $r->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="primary">
                                                Assign
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <strong>No scheduled deliveries</strong>
                Orders with delivery schedules will appear here.
            </div>
        @endif
    </section>

</div>

@endsection
