<?php

namespace App\Http\Controllers;

use App\Product;
use App\Vendor;
use App\PurchaseOrder;
use App\StoreRecord;
use App\Services\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseListController extends Controller
{
    public function __construct()
    {
        try {
            $this->middleware(['auth', \App\Http\Middleware\ActiveAccount::class]);
            $this->middleware(function ($r, $next) {
                abort_unless($r->user()->canManage('purchase-list'), 403);
                return $next($r);
            });
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function categories($rootsOnly = false)
    {
        try {
            $categories = StoreRecord::in('categories')->where('active', true)->orderBy('name')->get();
            return $rootsOnly ? $categories->filter(function ($category) {
                return empty($category->parent_id) && empty($category->data['parent_id']);
            })->values() : $categories;
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function categoryPair($categoryId, $subcategoryId)
    {
        try {
            $category = StoreRecord::in('categories')->where('active', true)->find($categoryId);
            $sub = \App\Subcategory::where('active', true)->find($subcategoryId);
            if (!$category || !$sub || (int) $sub->category_id !== (int) $category->id) {
                Commerce::error('Select a subcategory belonging to the selected category.');
            }
            return [$category, $sub];
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function index()
    {
        try {
            return view('admin.purchase-list.index', [
                'vendors' => Vendor::where('active', true)->orderBy('name')->get(),
                'categories' => $this->categories(true),
                'orders' => PurchaseOrder::with('vendor', 'creator')->latest()->paginate(15),
                'requestKey' => (string) Str::uuid(),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function vendor(Vendor $vendor)
    {
        try {
            abort_unless($vendor->active, 404);
            return response()->json([
                'id' => $vendor->id,
                'name' => $vendor->name,
                'data' => [
                    'vendor_code' => $vendor->vendor_code ?? data_get($vendor->data, 'vendor_code'),
                    'contact_person' => $vendor->contact_person ?? data_get($vendor->data, 'contact_person'),
                    'phone' => $vendor->phone ?? data_get($vendor->data, 'phone'),
                    'email' => $vendor->email ?? data_get($vendor->data, 'email'),
                    'address' => $vendor->address ?? data_get($vendor->data, 'address'),
                    'gst_number' => $vendor->gst_number ?? data_get($vendor->data, 'gst_number'),
                ],
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function subcategories(Request $r)
    {
        try {
            $v = $r->validate(['category_id' => 'required|integer|exists:categories,id']);
            abort_unless(StoreRecord::in('categories')->where('active', true)->find($v['category_id']), 404);
            return response()->json(\App\Subcategory::where('active', true)->where('category_id', $v['category_id'])->orderBy('name')->get()->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->name];
            }));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function products(Request $r)
    {
        try {
            $v = $r->validate(['category_id' => 'required|integer', 'subcategory_id' => 'required|integer']);
            $this->categoryPair($v['category_id'], $v['subcategory_id']);
            return response()->json(Product::where('active', true)->where('subcategory_id', $v['subcategory_id'])->orderBy('name')->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock' => $p->available_stock,
                ];
            }));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function store(Request $r)
    {
        try {
            $v = $r->validate([
                'request_key' => 'required|uuid',
                'vendor_id' => 'required|integer|exists:vendors,id',
                'notes' => 'nullable|string|max:2000',
                'items' => 'required|array|min:1|max:100',
                'items.*.product_id' => 'required|integer|distinct|exists:products,id',
                'items.*.category_id' => 'required|integer|exists:categories,id',
                'items.*.subcategory_id' => 'required|integer|exists:subcategories,id',
                'items.*.quantity' => 'required|integer|between:1,1000000',
            ]);
            $order = DB::transaction(function () use ($v) {
                $existing = PurchaseOrder::where('request_key', $v['request_key'])->first();
                if ($existing) {
                    abort_unless((int) $existing->created_by === (int) auth()->id(), 403);
                    return $existing;
                }
                $vendor = Vendor::where('id', $v['vendor_id'])->lockForUpdate()->firstOrFail();
                $existing = PurchaseOrder::where('request_key', $v['request_key'])->first();
                if ($existing) {
                    abort_unless((int) $existing->created_by === (int) auth()->id(), 403);
                    return $existing;
                }
                if (!$vendor->active) {
                    Commerce::error('Select an active vendor.');
                }
                $lines = [];
                foreach ($v['items'] as $line) {
                    list($category, $sub) = $this->categoryPair($line['category_id'], $line['subcategory_id']);
                    $p = Product::where('id', $line['product_id'])->lockForUpdate()->first();
                    if (!$p || !$p->active || (int) $p->subcategory_id !== (int) $sub->id) {
                        Commerce::error('A selected product is unavailable or does not belong to its subcategory.');
                    }
                    $lines[] = [
                        'product_id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'category_name' => $category->name,
                        'subcategory_name' => $sub->name,
                        'existing_stock' => $p->available_stock,
                        'quantity' => $line['quantity'],
                    ];
                }
                $o = PurchaseOrder::create([
                    'number' => 'PO-' . strtoupper(Str::random(12)),
                    'request_key' => $v['request_key'],
                    'vendor_id' => $vendor->id,
                    'order_date' => today()->toDateString(),
                    'status' => 'Placed',
                    'created_by' => auth()->id(),
                    'notes' => $v['notes'] ?? null,
                ]);
                $o->update(['number' => 'PO-' . today()->format('Ymd') . '-' . str_pad($o->id, 6, '0', STR_PAD_LEFT)]);
                $o->items()->createMany($lines);
                \App\Services\VendorFlow::notify($vendor, 'New purchase order ' . $o->number, ['purchase_order_id' => $o->id]);
                Commerce::log('Purchase order placed', ['number' => $o->number]);
                return $o;
            }, 3);
            return redirect()->route('admin.purchase-list.show', $order)->with('success', 'Purchase order ' . $order->number . ' has been placed successfully.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder->load('vendor', 'creator', 'items');
            return view('admin.purchase-list.show', ['order' => $purchaseOrder]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}
