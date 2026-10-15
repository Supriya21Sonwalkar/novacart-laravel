
@extends('layouts.admin')

@section('admin_title', 'Delivery operations')

@section('admin_content')

{{-- =========================================================
     SERVICE AREAS
========================================================= --}}
<section class="admin-card">
    <h2>Service areas</h2>

    <p>
        Use real serviceable pincodes. Travel minutes is your operational
        estimate; express promises must cover it.
    </p>

    {{-- Update existing service areas --}}
    @foreach($zones as $z)
        <form
            method="post"
            action="{{ url('/admin/delivery/zones') }}"
            class="form-grid"
        >
            @csrf

            <input type="hidden" name="id" value="{{ $z->id }}">

            @include('delivery.zone-fields', ['z' => $z])

            <button type="submit" class="primary">
                Update area
            </button>
        </form>
    @endforeach

    {{-- Create a new service area --}}
    <h3>Add service area</h3>

    <form
        method="post"
        action="{{ url('/admin/delivery/zones') }}"
        class="form-grid"
    >
        @csrf

        @include('delivery.zone-fields', ['z' => null])

        <button type="submit" class="primary">
            Create area
        </button>
    </form>
</section>


{{-- =========================================================
     DELIVERY SLOTS
========================================================= --}}
<section class="admin-card">
    <h2>Delivery slots</h2>

    <form
        method="post"
        action="{{ url('/admin/delivery/slots') }}"
        class="form-grid"
    >
        @csrf

        <label>
            Service area
            <select name="zone_id" required>
                @foreach($zones as $z)
                    <option value="{{ $z->id }}">
                        {{ $z->name }} — {{ $z->city }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Starts
            <input
                type="datetime-local"
                name="starts_at"
                required
            >
        </label>

        <label>
            Ends
            <input
                type="datetime-local"
                name="ends_at"
                required
            >
        </label>

        <label>
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

        <button type="submit" class="primary">
            Create slot
        </button>
    </form>

    @foreach($slots as $s)
        <p>
            Area #{{ $s->zone_id }}
            · {{ $s->starts_at }}
            — {{ $s->ends_at }}
            · Capacity {{ $s->capacity }}
        </p>
    @endforeach
</section>


{{-- =========================================================
     ASSIGN SERVICE AREAS TO DELIVERY PARTNERS
========================================================= --}}
<section class="admin-card">
    <h2>Assign Service Areas to Delivery Partners</h2>

    <p>
        Select the areas each delivery partner is allowed to serve.
        A partner can serve multiple areas.
    </p>

    @forelse($riders as $rider)

        @php
            $assignedZoneIds = isset($riderZoneAssignments[$rider->id])
                ? $riderZoneAssignments[$rider->id]
                    ->pluck('zone_id')
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->toArray()
                : [];
        @endphp

        <form
            method="post"
            action="{{ url('/admin/delivery/riders/' . $rider->id . '/zones') }}"
            class="form-grid"
        >
            @csrf

            <div>
                <strong>{{ $rider->name }}</strong>
                <p>{{ $rider->email }}</p>
            </div>

            <label>
                Service areas

                <select
                    name="zone_ids[]"
                    multiple
                    size="4"
                >
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

            <button type="submit" class="primary">
                Save Areas
            </button>
        </form>

        <hr>

    @empty
        <p>
            No active delivery partners found. Create or activate a user
            with the delivery partner role first.
        </p>
    @endforelse
</section>


{{-- =========================================================
     ORDER ASSIGNMENT
========================================================= --}}
<section class="admin-card">
    <h2>Assign delivery partners to orders</h2>

    <p>
        Assign an available delivery partner to an order with an existing
        delivery schedule.
    </p>

    @forelse($deliveries as $d)

        @php
            $o = \App\Order::find($d->order_id);
        @endphp

        @if($o)
            <form
                method="post"
                action="{{ url('/admin/delivery/orders/' . $d->order_id . '/assign') }}"
                class="form-grid"
            >
                @csrf

                <div>
                    <p>
                        <a href="{{ url('/orders/' . $o->id) }}">
                            {{ $o->number }}
                        </a>
                        · {{ $o->status }}
                        · Due {{ $d->promised_to }}
                    </p>
                </div>

                <label>
                    Delivery partner

                    <select name="rider_id" required>
                        <option value="">
                            Choose delivery partner
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
                </label>

                <button type="submit" class="primary">
                    Assign partner
                </button>
            </form>
        @endif

    @empty
        <p>No scheduled deliveries are available for assignment.</p>
    @endforelse
</section>

@endsection
