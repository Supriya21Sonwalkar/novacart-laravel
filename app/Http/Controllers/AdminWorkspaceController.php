<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Product;
use App\Order;
use App\User;
use App\Outward;
use App\StoreRecord;
use Illuminate\Http\Request;

class AdminWorkspaceController extends Controller
{
    public function __construct()
    {
        try {
            $this->middleware(['auth', \App\Http\Middleware\ActiveAccount::class]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function profile()
    {
        try {
            abort_unless(auth()->user()->canManage('dashboard'), 403);
            return view('admin.team-profile');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function saveProfile(Request $r)
    {
        try {
            abort_unless($r->user()->canManage('dashboard'), 403);
            $r->merge(['email' => strtolower(trim((string) $r->input('email')))]);
            $v = $r->validate([
                'name' => 'required|string|max:100',
                'email' => [
                    'required',
                    'email',
                    'max:190',
                    \Illuminate\Validation\Rule::unique('users')->ignore($r->user()->id),
                ],
            ]);
            $v['email'] = strtolower(trim($v['email']));
            if ($v['email'] !== $r->user()->email) {
                $r->user()->email_verified_at = null;
            }
            $r->user()->fill($v)->save();
            return back()->with('success', 'Your store account details were updated.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function home(Request $request)
    {
        try {
            abort_unless(auth()->user()->canManage('dashboard'), 403);
            $u = auth()->user();
            $cards = [];
            $dates = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
            $from = $dates['from'] ?? today()->startOfMonth()->toDateString();
            $to = $dates['to'] ?? today()->toDateString();
            if ($from > $to) {
                throw \Illuminate\Validation\ValidationException::withMessages(['to' => 'The end date must follow the start date.']);
            }
            $periodOrders = Order::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('status', '!=', 'Cancelled');
            $metrics = [];
            $sales = collect();
            $top = collect();
            if ($u->canManage('reports')) {
                $refunds = \Illuminate\Support\Facades\DB::table('order_returns')->where('refund_status', 'Test refund recorded')->whereDate('updated_at', '>=', $from)->whereDate('updated_at', '<=', $to)->sum('refund_amount');
                $gross = (clone $periodOrders)->sum('total');
                $metrics = [
                    [
                        'Order value',
                        $gross,
                        'Includes tax and delivery; test orders',
                    ],
                    [
                        'Recorded refunds',
                        $refunds,
                        'Recorded in this period; no money moved',
                    ],
                    [
                        'Order value less refunds',
                        $gross - $refunds,
                        'Order value and refund activity for this period',
                    ],
                ];
                if ($u->canManage('inventory')) {
                    $metrics[] = [
                        'Received purchase value',
                        \App\Inward::whereDate('inward_date', '>=', $from)->whereDate('inward_date', '<=', $to)->sum('total'),
                        'Recorded stock receipts including purchase tax',
                    ];
                }
                $sales = (clone $periodOrders)->select(\Illuminate\Support\Facades\DB::raw('DATE(created_at) as day'), \Illuminate\Support\Facades\DB::raw('SUM(total) as total'))->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'))->orderBy('day')->get();
                $top = \Illuminate\Support\Facades\DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.status', '!=', 'Cancelled')->whereDate('orders.created_at', '>=', $from)->whereDate('orders.created_at', '<=', $to)->select('order_items.product_id', 'order_items.name', \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as units'), \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity * order_items.price) as value'))->groupBy('order_items.product_id', 'order_items.name')->orderByDesc('units')->limit(5)->get();
            }
            if ($u->canManage('orders')) {
                $cards[] = [
                    'label' => 'Orders to prepare',
                    'count' => Order::whereIn('status', ['Placed', 'Confirmed'])->count(),
                    'url' => '/admin/orders?preset=prepare',
                    'hint' => 'Placed and confirmed orders',
                ];
                $cards[] = [
                    'label' => 'In delivery',
                    'count' => Order::whereIn('status', [
                        'Shipped',
                        'Picked up',
                        'On the way',
                    ])->count(),
                    'url' => '/admin/orders?preset=delivery',
                    'hint' => 'Shipped and active rider deliveries',
                ];
            }
            if ($u->canManage('inventory')) {
                $cards[] = [
                    'label' => 'Awaiting dispatch',
                    'count' => Outward::whereIn('status', ['Pending', 'Packed'])->count(),
                    'url' => '/admin/outward',
                    'hint' => 'Confirmed outward entries',
                ];
                $cards[] = [
                    'label' => 'Low stock',
                    'count' => Product::where('active', true)->where('stock', '<=', 5)->count(),
                    'url' => '/admin/products?stock=low',
                    'hint' => '5 units or fewer',
                ];
                $cards[] = [
                    'label' => 'Expiring within 7 days',
                    'count' => \Illuminate\Support\Facades\DB::table('product_batches')->where('remaining_quantity', '>', 0)->whereBetween('expires_on', [today()->toDateString(), today()->addDays(7)->toDateString()])->count(),
                    'url' => '/admin/batches?expiry=soon',
                    'hint' => 'Inspect batches; FIFO still uses receipt date',
                ];
            }
            if ($u->canManage('support_messages')) {
                $cards[] = [
                    'label' => 'Unread support messages',
                    'count' => StoreRecord::in('support_messages')->where('read', false)->count(),
                    'url' => '/admin/support_messages?unread=1',
                    'hint' => 'Customer enquiries',
                ];
            }
            if ($u->canManage('inventory')) {
                $cards[] = [
                    'label' => 'Expired stock',
                    'count' => \Illuminate\Support\Facades\DB::table('product_batches')->where('remaining_quantity', '>', 0)->where('expires_on', '<', today()->toDateString())->count(),
                    'url' => '/admin/batches?expiry=expired',
                    'hint' => 'Inspect and remove expired goods through Outward',
                ];
            }
            if ($u->canManage('vendors')) {
                $cards[] = [
                    'label' => 'Supplier purchase orders',
                    'count' => \App\PurchaseOrder::whereIn('status', ['Placed', 'Partially Received'])->count(),
                    'url' => '/admin/purchase-list',
                    'hint' => 'Receive goods through Inward against the purchase order',
                ];
            }
            if ($u->canManage('orders')) {
                $cards[] = [
                    'label' => 'Return requests',
                    'count' => \Illuminate\Support\Facades\DB::table('order_returns')->where('status', 'Requested')->count(),
                    'url' => '/admin/returns',
                    'hint' => 'Inspect goods before restoring stock or recording a refund',
                ];
            }
            return view('admin.workspace', [
                'from' => $from,
                'to' => $to,
                'metrics' => $metrics,
                'sales' => $sales,
                'top' => $top,
                'cards' => $cards,
                'recent' => $u->canManage('orders') ? Order::latest()->limit(8)->get() : collect(),
                'low' => $u->canManage('inventory') ? Product::where('active', true)->where('stock', '<=', 5)->orderBy('stock')->limit(8)->get() : collect(),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function search(Request $r)
    {
        try {
            $r->validate(['q' => 'nullable|string|max:100']);
            $term = trim($r->input('q', ''));
            $u = $r->user();
            abort_unless(collect(array_keys(config('store.modules')))->contains(function ($m) use ($u) {
                return $u->canManage($m);
            }), 403);
            $results = [];
            if ($term !== '') {
                $like = '%' . $term . '%';
                if ($u->canManage('products')) {
                    foreach (Product::where(function ($q) use ($like) {
                        $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhereHas('category', function ($q) use ($like) {
                            $q->where('name', 'like', $like);
                        });
                    })->limit(10)->get() as $p) {
                        $results[] = [
                            'kind' => 'Product',
                            'title' => $p->name,
                            'detail' => $p->sku . ' · ' . $p->stock . ' in stock',
                            'url' => route('admin.edit', ['products', $p->id]),
                        ];
                    }
                }
                if ($u->canManage('orders')) {
                    foreach (Order::where(function ($q) use ($like) {
                        $q->where('number', 'like', $like)->orWhere('invoice_number', 'like', $like)->orWhere('email', 'like', $like);
                    })->limit(10)->get() as $o) {
                        $results[] = [
                            'kind' => 'Order',
                            'title' => $o->number,
                            'detail' => $o->email . ' · ' . $o->status,
                            'url' => route('orders.show', $o),
                        ];
                    }
                }
                if ($u->canManage('users')) {
                    foreach (User::where(function ($q) use ($like) {
                        $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like);
                    })->limit(10)->get() as $c) {
                        $results[] = [
                            'kind' => 'Account',
                            'title' => $c->name,
                            'detail' => $c->email,
                            'url' => route('admin.edit', ['users', $c->id]),
                        ];
                    }
                }
                if ($u->canManage('inventory')) {
                    foreach (Outward::where('number', 'like', $like)->orWhere('tracking_number', 'like', $like)->limit(10)->get() as $o) {
                        $results[] = [
                            'kind' => 'Outward',
                            'title' => $o->number,
                            'detail' => $o->type . ' · ' . $o->status,
                            'url' => route('admin.outward.show', $o),
                        ];
                    }
                }
                if ($u->canManage('inventory')) {
                    foreach (\Illuminate\Support\Facades\DB::table('product_batches')->join('products', 'products.id', '=', 'product_batches.product_id')->where(function ($q) use ($like) {
                        $q->where('batch_number', 'like', $like)->orWhere('supplier_invoice_no', 'like', $like);
                    })->select('product_batches.*', 'products.name')->limit(10)->get() as $b) {
                        $results[] = [
                            'kind' => 'Stock batch',
                            'title' => $b->batch_number,
                            'detail' => $b->name . ' · ' . ($b->supplier_invoice_no ?: 'Opening stock'),
                            'url' => route('admin.batches', ['q' => $term]),
                        ];
                    }
                }
            }
            return view('admin.search', ['results' => $results, 'term' => $term]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function bulkPack(Request $r)
    {
        try {
            abort_unless($r->user()->canManage('orders') && $r->user()->canManage('inventory'), 403);
            $v = $r->validate([
                'order_ids' => 'required|array|min:1|max:20',
                'order_ids.*' => 'required|integer|distinct|exists:orders,id',
            ]);
            DB::transaction(function () use ($v) {
                foreach (collect($v['order_ids'])->sort()->values() as $id) {
                    $o = Outward::where('active_order_id', $id)->first();
                    if (!$o || $o->status !== 'Pending') {
                        \App\Services\Commerce::error('Every selected order must have a confirmed pending outward entry.');
                    }
                    \App\Services\OutwardFlow::transition($o, 'pack');
                }
            }, 3);
            return back()->with('success', 'Selected orders marked packed.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}