<?php

namespace App\Http\Controllers;

use App\Order;
use App\User;
use App\StoreRecord;
use App\Services\Commerce;
use App\Services\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function location(Request $r)
    {
        try {
            $v = $r->validate(['pincode' => 'required|regex:/^[1-9][0-9]{5}$/']);
            session(['delivery_pincode' => $v['pincode']]);
            return back()->with('notice', Delivery::zone($v['pincode']) ? 'Delivery area selected. Check available times at checkout.' : 'Delivery is not yet available here. You can still browse.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function suggestions(Request $r)
    {
        try {
            $r->validate(['q' => 'nullable|string|max:100']);
            $q = \App\Product::where('active', true);
            \App\Services\Catalog::search($q, $r->input('q', ''));
            return response()->json($q->limit(8)->get([
                'id',
                'name',
                'image',
                'price',
            ]));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function slots(Request $r)
    {
        try {
            $r->validate(['address_id' => 'required|integer']);
            $a = Commerce::owned(\App\Address::query())->findOrFail($r->address_id);
            return response()->json([
                'options' => Delivery::options(Commerce::items(), $a->pincode),
                'required' => Delivery::requiresSchedule(Commerce::items()),
                'message' => Delivery::zone($a->pincode) ? 'No compatible delivery time? Separate fresh and standard products or try another address.' : 'This pincode is outside the delivery area.',
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function rider()
    {
        try {
            abort_unless(auth()->check() && auth()->user()->active && optional(StoreRecord::in('roles')->find(auth()->user()->role_id))->name === 'delivery partner', 403);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function riderPanel()
    {
        try {
            $this->rider();
            $deliveries = DB::table('deliveries')->where('rider_id', auth()->id())->orderByDesc('id')->get()->map(function ($d) {
                $d->order = Order::find($d->order_id);
                return $d;
            });
            return view('delivery.rider', compact('deliveries'));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function update(Request $r, Order $order)
    {
        try {
            $this->rider();
            return DB::transaction(function () use ($r, $order) {
                $o = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
                $d = DB::table('deliveries')->where('order_id', $o->id)->lockForUpdate()->first();
                abort_unless($d && (int) $d->rider_id === auth()->id(), 403);
                abort_if(in_array($o->status, [
                    'Cancelled',
                    'Refunded',
                    'Delivered',
                ], true), 409);
                $v = $r->validate([
                    'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
                    'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
                    'status' => 'nullable|in:Picked up,On the way,Delivered',
                    'eta_minutes' => 'nullable|integer|between:1,240',
                    'proof' => 'nullable|required_if:status,Delivered|string|min:5|max:500',
                    'failure_reason' => 'nullable|string|min:5|max:500',
                ]);
                if ((isset($v['latitude']) || isset($v['eta_minutes'])) && !in_array($o->status, [
                    'Shipped',
                    'Picked up',
                    'On the way',
                ], true)) {
                    Commerce::error('Dispatch the order before sharing location or an arrival estimate.');
                }
                if (!empty($v['failure_reason']) && !empty($v['status'])) {
                    Commerce::error('Report a failed attempt or update progress separately.');
                }
                if (isset($v['latitude'])) {
                    DB::table('deliveries')->where('id', $d->id)->update([
                        'latitude' => $v['latitude'],
                        'longitude' => $v['longitude'],
                        'location_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if (isset($v['eta_minutes'])) {
                    DB::table('deliveries')->where('id', $d->id)->update(['eta_at' => now()->addMinutes($v['eta_minutes']), 'updated_at' => now()]);
                }
                if (!empty($v['failure_reason'])) {
                    if (!in_array($o->status, [
                        'Shipped',
                        'Picked up',
                        'On the way',
                    ], true)) {
                        Commerce::error('Dispatch the order before reporting a failed delivery.');
                    }
                    DB::table('deliveries')->where('id', $d->id)->update([
                        'failure_reason' => $v['failure_reason'],
                        'failed_at' => now(),
                        'eta_at' => null,
                        'updated_at' => now(),
                    ]);
                    Commerce::notify($o, 'Delivery attempt failed. The store will arrange the next attempt.');
                }
                if (!empty($v['status'])) {
                    $next = [
                        'Shipped' => 'Picked up',
                        'Picked up' => 'On the way',
                        'On the way' => 'Delivered',
                    ];
                    if (($next[$o->status] ?? null) !== $v['status']) {
                        Commerce::error('Follow pickup → on the way → delivered.');
                    }
                    Commerce::status($o, $v['status'], true, ['delivery_proof' => $v['proof'] ?? '']);
                    DB::table('deliveries')->where('id', $d->id)->update([
                        'proof' => $v['proof'] ?? null,
                        'failure_reason' => null,
                        'failed_at' => null,
                        'updated_at' => now(),
                    ]);
                }
                return $r->expectsJson() ? response()->json(['saved' => true]) : back()->with('success', 'Delivery updated.');
            });
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function tracking(Order $order)
    {
        try {
            abort_unless(auth()->check() && ((int) auth()->id() === (int) $order->user_id || auth()->user()->canManage('orders')), 403);
            $d = DB::table('deliveries')->where('order_id', $order->id)->first();
            $active = !in_array($order->status, [
                'Delivered',
                'Cancelled',
                'Refunded',
            ], true);
            return response()->json([
                'status' => $order->status,
                'timeline' => $order->timeline,
                'delivery' => $d ? [
                    'failure_reason' => $d->failure_reason,
                    'from' => $d->promised_from,
                    'to' => $d->promised_to,
                    'eta' => $active ? $d->eta_at : null,
                    'latitude' => $active ? $d->latitude : null,
                    'longitude' => $active ? $d->longitude : null,
                    'updated' => $active ? $d->location_at : null,
                    'rider' => $d->rider_id ? optional(User::find($d->rider_id))->name : null,
                ] : null,
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function admin()
    {
        try {
            abort_unless(auth()->user()->canManage('orders'), 403);
            $zones = DB::table('delivery_zones')->orderBy('city')->orderBy('name')->get();
            $slots = DB::table('delivery_slots')->whereDate('starts_at', today())->where('ends_at', '>', now())->orderBy('starts_at')->get();
            $deliveries = DB::table('deliveries')->orderByDesc('id')->limit(50)->get();
            $riders = User::where('active', true)->whereIn('role_id', StoreRecord::in('roles')->where('name', 'delivery partner')->pluck('id'))->orderBy('name')->get();
            $riderZoneAssignments = DB::table('delivery_zone_riders')->join('delivery_zones', 'delivery_zones.id', '=', 'delivery_zone_riders.zone_id')->select('delivery_zone_riders.rider_id', 'delivery_zone_riders.zone_id', 'delivery_zones.name as zone_name', 'delivery_zones.city as zone_city')->orderBy('delivery_zones.city')->orderBy('delivery_zones.name')->get()->groupBy('rider_id');
            return view('delivery.admin', compact('zones', 'slots', 'deliveries', 'riders', 'riderZoneAssignments'));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function zoneSave(Request $r)
    {
        try {
            abort_unless(auth()->user()->canManage('orders'), 403);
            $v = $r->validate([
                'name' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'pincodes' => 'required|regex:/^[1-9][0-9]{5}(?:\\s*,\\s*[1-9][0-9]{5})*$/',
                'opens_at' => 'required|date_format:H:i',
                'closes_at' => 'required|date_format:H:i|after:opens_at',
                'travel_minutes' => 'required|integer|between:1,240',
            ]);
            $v['express'] = $r->boolean('express');
            $v['active'] = $r->boolean('active');
            $v['updated_at'] = now();
            if ($r->filled('id')) {
                DB::table('delivery_zones')->where('id', $r->validate(['id' => 'required|exists:delivery_zones,id'])['id'])->update($v);
            } else {
                DB::table('delivery_zones')->insert($v + ['created_at' => now()]);
            }
            return back()->with('success', 'Service area saved.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function slotSave(Request $r)
    {
        try {
            abort_unless(auth()->user()->canManage('orders'), 403);
            $v = $r->validate([
                'zone_id' => 'required|exists:delivery_zones,id',
                'starts_at' => 'required|date|after:now',
                'ends_at' => 'required|date|after:starts_at',
                'capacity' => 'required|integer|between:1,1000',
            ]);
            $zone = DB::table('delivery_zones')->find($v['zone_id']);
            $start = \Carbon\Carbon::parse($v['starts_at']);
            $end = \Carbon\Carbon::parse($v['ends_at']);
            if (!$start->isSameDay($end) || $start->format('H:i:s') < $zone->opens_at || $end->format('H:i:s') > $zone->closes_at) {
                Commerce::error('Slots must stay within the service area opening hours.');
            }
            $v['starts_at'] = $start;
            $v['ends_at'] = $end;
            DB::table('delivery_slots')->insert($v + ['created_at' => now(), 'updated_at' => now()]);
            return back()->with('success', 'Delivery slot created.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function saveRiderZones(Request $r, $rider)
    {
        try {
            abort_unless(auth()->user()->canManage('orders'), 403);
            $v = $r->validate(['zone_ids' => 'nullable|array', 'zone_ids.*' => 'integer|distinct|exists:delivery_zones,id']);
            $partner = User::where('id', $rider)->where('active', true)->firstOrFail();
            abort_unless(optional(StoreRecord::in('roles')->find($partner->role_id))->name === 'delivery partner', 422);
            $zoneIds = isset($v['zone_ids']) ? array_values(array_unique(array_map('intval', $v['zone_ids']))) : [];
            DB::transaction(function () use ($partner, $zoneIds) {
                $partner = User::where('id', $partner->id)->where('active', true)->lockForUpdate()->firstOrFail();
                abort_unless(optional(StoreRecord::in('roles')->find($partner->role_id))->name === 'delivery partner', 422);
                DB::table('delivery_zone_riders')->where('rider_id', $partner->id)->delete();
                foreach ($zoneIds as $zoneId) {
                    DB::table('delivery_zone_riders')->insert([
                        'rider_id' => $partner->id,
                        'zone_id' => $zoneId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
            return back()->with('success', 'Service areas saved for ' . $partner->name . '.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function assign(Request $r, Order $order)
    {
        try {
            abort_unless(auth()->user()->canManage('orders'), 403);
            $v = $r->validate(['rider_id' => 'required|exists:users,id']);
            DB::transaction(function () use ($v, $order) {
                $o = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
                $u = User::where('id', $v['rider_id'])->lockForUpdate()->firstOrFail();
                abort_unless($u->active && optional(StoreRecord::in('roles')->find($u->role_id))->name === 'delivery partner', 422);
                if (in_array($o->status, [
                    'Cancelled',
                    'Delivered',
                    'Refunded',
                ], true)) {
                    Commerce::error('This delivery is closed.');
                }

                // Find the existing delivery schedule and its service zone.
                $delivery = DB::table('deliveries')->where('order_id', $o->id)->lockForUpdate()->first();
                if (!$delivery) {
                    Commerce::error('This order has no delivery schedule.');
                }

                // Only allow riders assigned to this delivery zone.
                $assignedToZone = DB::table('delivery_zone_riders')->where('rider_id', $u->id)->where('zone_id', $delivery->zone_id)->exists();
                if (!$assignedToZone) {
                    Commerce::error('This delivery partner is not assigned to the order service area.');
                }

                // Prevent the rider from receiving another active delivery.
                $hasActiveDelivery = DB::table('deliveries')->join('orders', 'orders.id', '=', 'deliveries.order_id')->where('deliveries.rider_id', $u->id)->where('deliveries.order_id', '!=', $o->id)->whereNotIn('orders.status', [
                    'Delivered',
                    'Cancelled',
                    'Refunded',
                ])->exists();
                if ($hasActiveDelivery) {
                    Commerce::error('This rider already has an active delivery.');
                }
                DB::table('deliveries')->where('order_id', $o->id)->update([
                    'rider_id' => $u->id,
                    'latitude' => null,
                    'longitude' => null,
                    'location_at' => null,
                    'eta_at' => null,
                    'updated_at' => now(),
                ]);
                Commerce::notify($o, 'Delivery partner assigned: ' . $u->name);
            }, 3);
            return back()->with('success', 'Rider assigned.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}
