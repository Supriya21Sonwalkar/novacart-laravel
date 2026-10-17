<?php

namespace App\Http\Controllers;

use App\Product;
use App\Review;
use App\Address;
use App\Order;
use App\StoreRecord;
use App\Services\Commerce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function home()
    {
        try {
            return view('shop.home', [
                'products' => Product::where('active', true)->orderByDesc('featured')->latest()->take(8)->get(),
                'banners' => StoreRecord::in('banners')->where('active', true)->get(),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function shop(Request $r)
    {
        try {
            $r->validate([
                'q' => 'nullable|string|max:100',
                'category' => 'nullable|integer',
                'subcategory' => 'nullable|integer',
                'brand' => 'nullable|integer',
                'min' => 'nullable|numeric|min:0',
                'max' => 'nullable|numeric|min:0',
                'rating' => 'nullable|numeric|between:1,5',
                'sort' => 'nullable|in:price_low,price_high,newest,popular',
            ]);
            if ($r->filled('min') && $r->filled('max') && (float) $r->max < (float) $r->min) {
                Commerce::error('Maximum price must not be less than minimum price.');
            }
            $q = Product::where('active', true);
            if ($r->filled('q')) {
                \App\Services\Catalog::search($q, $r->input('q'));
            }
            $categoryId = $r->filled('category') ? (int) $r->input('category') : null;
            $subcategoryId = $r->filled('subcategory') ? (int) $r->input('subcategory') : null;
            if ($categoryId) {
                $mainCategory = StoreRecord::in('categories')->where('active', true)->find($categoryId);
                abort_unless($mainCategory && empty($mainCategory->parent_id) && empty($mainCategory->data['parent_id']), 404);
                if ($subcategoryId) {
                    $subcategory = \App\Subcategory::where('active', true)->find($subcategoryId);
                    abort_unless($subcategory && (int) $subcategory->category_id === $categoryId, 404);
                    $q->where('subcategory_id', $subcategoryId);
                } else {
                    $q->where('category_id', $categoryId);
                }
            } elseif ($subcategoryId) {
                // A subcategory cannot be selected without its parent.
                $r->merge(['subcategory' => null]);
            }
            if ($r->filled('brand')) {
                $q->where('brand_id', $r->input('brand'));
            }
            if ($r->filled('min')) {
                $q->where('price', '>=', max(0, (float) $r->input('min')) * 100);
            }
            if ($r->filled('max')) {
                $q->where('price', '<=', max(0, (float) $r->input('max')) * 100);
            }
            if ($r->boolean('stock')) {
                $q->where(function ($q) {
                    $q->whereExists(function ($b) {
                        $b->select(DB::raw(1))->from('product_batches')->whereColumn('product_batches.product_id', 'products.id')->where('remaining_quantity', '>', 0)->where(function ($valid) {
                            $valid->whereNotIn('products.delivery_type', ['fresh', 'dairy'])->orWhereNotNull('product_batches.expires_on');
                        })->where(function ($e) {
                            $e->whereNull('expires_on')->orWhere('expires_on', '>=', today()->toDateString());
                        });
                    })->orWhere(function ($legacy) {
                        $legacy->where('stock', '>', 0)->where(function ($valid) {
                            $valid->whereNotIn('products.delivery_type', ['fresh', 'dairy'])->orWhereNotNull('products.expires_on');
                        })->where(function ($e) {
                            $e->whereNull('expires_on')->orWhere('expires_on', '>=', today()->toDateString());
                        })->whereNotExists(function ($b) {
                            $b->select(DB::raw(1))->from('product_batches')->whereColumn('product_batches.product_id', 'products.id');
                        });
                    });
                });
            }
            if ($r->boolean('offers')) {
                $q->whereColumn('old_price', '>', 'price');
            }
            if ($r->filled('rating')) {
                $q->whereIn('id', DB::table('reviews')->select('product_id')->where('approved', true)->groupBy('product_id')->havingRaw('AVG(rating) >= ?', [(float) $r->input('rating')]));
            }
            $sort = $r->input('sort');
            if ($sort === 'price_low') {
                $q->orderBy('price');
            } elseif ($sort === 'price_high') {
                $q->orderByDesc('price');
            } elseif ($sort === 'newest') {
                $q->latest();
            } elseif ($sort === 'popular') {
                $q->orderByRaw('(SELECT COALESCE(SUM(quantity),0) FROM order_items WHERE order_items.product_id=products.id) DESC');
            } else {
                $q->orderByDesc('featured')->latest();
            }
            $categories = StoreRecord::in('categories')->where('active', true)->whereNull('parent_id')->orderBy('name')->get();
            $subcategories = \App\Subcategory::where('active', true)->orderBy('name')->get();
            $brands = StoreRecord::in('brands')->where('active', true)->orderBy('name')->get();
            return view('shop.catalog', [
                'products' => $q->paginate(9)->appends($r->query()),
                'categories' => $categories,
                'subcategories' => $subcategories,
                'brands' => $brands,
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function product(Product $product)
    {
        try {
            abort_unless($product->active, 404);
            $recent = session('recent_products', []);
            array_unshift($recent, $product->id);
            session(['recent_products' => array_slice(array_unique($recent), 0, 6)]);
            return view('shop.product', [
                'product' => $product,
                'related' => Product::where('active', true)->where('id', '!=', $product->id)->where('category_id', $product->category_id)->take(4)->get(),
                'reviews' => $product->reviews()->where('approved', true)->with('user')->latest()->get(),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function cart()
    {
        try {
            $items = Commerce::items();
            $shipping = StoreRecord::in('shipping_methods')->where('active', true)->first();
            $totals = null;
            try {
                $totals = $items->count() && $shipping ? Commerce::totals($items, $shipping->id, session('coupon')) : null;
            } catch (\Illuminate\Validation\ValidationException $e) {
                session()->forget('coupon');
                session()->flash('notice', head($e->errors()['store']));
            }
            return view('shop.cart', compact('items', 'totals'));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function add(Request $r, Product $product)
    {
        try {
            $v = $r->validate(['quantity' => 'required|integer|min:1|max:999', 'variant' => 'required|string|max:120']);
            Commerce::add($product, $v['quantity'], $v['variant']);
            return $r->boolean('buy_now') ? redirect('/checkout') : back()->with('success', 'Added to your bag.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function updateCart(Request $r, $id)
    {
        try {
            $v = $r->validate(['quantity' => 'required|integer|min:1|max:999']);
            $c = Commerce::cart();
            abort_unless($c, 404);
            $i = DB::table('cart_items')->where('cart_id', $c->id)->find($id);
            abort_unless($i, 404);
            $p = Product::find($i->product_id);
            if (!$p || !$p->active || $v['quantity'] > \App\Services\BatchStock::available($p)) {
                Commerce::error('Quantity exceeds available stock.');
            }
            DB::table('cart_items')->where('id', $id)->update(['quantity' => $v['quantity'], 'updated_at' => now()]);
            return back();
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function removeCart($id)
    {
        try {
            $c = Commerce::cart();
            abort_unless($c, 404);
            DB::table('cart_items')->where('cart_id', $c->id)->where('id', $id)->delete();
            return back()->with('success', 'Item removed.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function clearCart()
    {
        try {
            if ($c = Commerce::cart()) {
                DB::table('cart_items')->where('cart_id', $c->id)->delete();
            }
            session()->forget('coupon');
            return back()->with('success', 'Your bag is cleared.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function coupon(Request $r)
    {
        try {
            $r->validate(['coupon' => 'nullable|string|max:32']);
            if (!$r->input('coupon')) {
                session()->forget('coupon');
                return back();
            }
            $s = StoreRecord::in('shipping_methods')->where('active', true)->first();
            Commerce::totals(Commerce::items(), $s ? $s->id : 0, $r->input('coupon'));
            session(['coupon' => strtoupper(trim($r->input('coupon')))]);
            return back()->with('success', 'Coupon applied.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function wishlist()
    {
        try {
            $w = DB::table('wishlists')->where('user_id', Auth::id())->first();
            $ids = $w ? DB::table('wishlist_items')->where('wishlist_id', $w->id)->pluck('product_id') : [];
            return view('shop.wishlist', ['products' => Product::whereIn('id', $ids)->where('active', true)->get()]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function wish(Product $product)
    {
        try {
            return DB::transaction(function () use ($product) {
                \App\User::where('id', Auth::id())->lockForUpdate()->firstOrFail();
                return $this->toggleWish($product);
            }, 3);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function toggleWish(Product $product)
    {
        try {
            $w = DB::table('wishlists')->where('user_id', Auth::id())->first();
            $id = $w ? $w->id : DB::table('wishlists')->insertGetId([
                'user_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('wishlist_items')->where(['wishlist_id' => $id, 'product_id' => $product->id]);
            if ($row->exists()) {
                $row->delete();
                $message = 'Removed from wishlist.';
            } else {
                DB::table('wishlist_items')->insert([
                    'wishlist_id' => $id,
                    'product_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $message = 'Saved to wishlist.';
            }
            return back()->with('success', $message);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function checkout(Request $r)
    {
        try {
            $items = Commerce::items();
            if (!$items->count()) {
                return redirect('/cart');
            }
            $shipping = StoreRecord::in('shipping_methods')->where('active', true)->get();
            if (!$shipping->count()) {
                Commerce::error('No delivery methods available.');
            }
            $selected = $r->query('shipping', $shipping->first()->id);
            $totals = Commerce::totals($items, $selected, session('coupon'));
            if (!session('checkout_key')) {
                session(['checkout_key' => (string) \Illuminate\Support\Str::uuid()]);
            }
            return view('shop.checkout', [
                'items' => $items,
                'totals' => $totals,
                'shipping' => $shipping,
                'addresses' => Commerce::owned(Address::query())->orderByDesc('is_default')->get(),
                'selected' => $selected,
                'requiresSchedule' => \App\Services\Delivery::requiresSchedule($items),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function place(Request $r)
    {
        try {
            $v = $r->validate([
                'key' => 'required|uuid',
                'address_id' => 'required|integer',
                'shipping_id' => 'required|integer',
                'payment' => 'required|string|max:100',
                'email' => 'required|email|max:190',
                'delivery_choice' => 'nullable|string|max:40',
            ]);
            $v['coupon'] = session('coupon');
            $o = Commerce::checkout($v);
            session()->forget(['checkout_key', 'coupon']);
            return redirect()->route('orders.show', $o)->with('success', 'Your test order has been placed. No payment was collected.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function orders()
    {
        try {
            return view('shop.orders', ['orders' => Commerce::owned(Order::query())->latest()->paginate(10)]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function order(Order $order)
    {
        try {
            abort_unless(Commerce::owned(Order::where('id', $order->id))->exists() || Auth::check() && Auth::user()->canManage('orders'), 403);
            return view('shop.order', ['order' => $order]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function status(Request $r, Order $order)
    {
        try {
            abort_unless(Commerce::owned(Order::where('id', $order->id))->exists(), 403);
            $r->validate(['status' => 'required|string']);
            Commerce::status($order, $r->input('status'));
            return back()->with('success', 'Order updated.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function reorder(Order $order)
    {
        try {
            return DB::transaction(function () use ($order) {
                return $this->reorderItems($order);
            }, 3);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    private function reorderItems(Order $order)
    {
        try {
            abort_unless(Commerce::owned(Order::where('id', $order->id))->exists(), 403);
            foreach ($order->items as $i) {
                $p = Product::find($i->product_id);
                if ($p && $p->active) {
                    Commerce::add($p, $i->quantity, $i->variant);
                }
            }
            return redirect('/cart')->with('success', 'Available items added to your bag.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function addressForm($id = null)
    {
        try {
            $address = $id ? Commerce::owned(Address::query())->findOrFail($id) : new Address();
            return view('shop.address', compact('address'));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function addressSave(Request $r, $id = null)
    {
        try {
            \App\Services\CustomerProfile::normalizeRequest($r);
            $v = $r->validate(\App\Services\CustomerProfile::addressRules());
            $a = $id ? Commerce::owned(Address::query())->findOrFail($id) : new Address();
            $v['is_default'] = $r->boolean('is_default');
            DB::transaction(function () use ($a, $v) {
                \App\User::where('id', Auth::id())->lockForUpdate()->firstOrFail();
                if ($a->exists) {
                    $a = Commerce::owned(Address::query())->where('id', $a->id)->lockForUpdate()->firstOrFail();
                }
                if ($v['is_default']) {
                    Commerce::owned(Address::query())->update(['is_default' => false]);
                }
                $a->fill($v);
                $a->user_id = Auth::id();
                $a->session_key = Commerce::owner();
                $a->save();
            });
            return redirect($r->input('return_to') === 'checkout' ? '/checkout' : '/account')->with('success', 'Address saved.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function addressDelete($id)
    {
        try {
            Commerce::owned(Address::query())->findOrFail($id)->delete();
            return back()->with('success', 'Address removed.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function account()
    {
        try {
            return view(request()->is('account/security') ? 'shop.account' : 'shop.account-dashboard', [
                'addresses' => Commerce::owned(Address::query())->orderByDesc('is_default')->get(),
                'reviews' => Review::where('user_id', Auth::id())->with('product')->latest()->get(),
            ]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function reviewForm(Product $product, $id = null)
    {
        try {
            $review = $id ? Review::where('user_id', Auth::id())->where('product_id', $product->id)->findOrFail($id) : new Review();
            return view('shop.review', compact('product', 'review'));
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function reviewSave(Request $r, Product $product, $id = null)
    {
        try {
            $v = $r->validate([
                'rating' => 'required|integer|between:1,5',
                'title' => 'required|string|max:100',
                'text' => 'required|string|max:5000',
                'image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp|dimensions:min_width=1,min_height=1|max:5120',
            ]);
            \App\Services\ReviewWriter::save($product, $id, $v, $r->file('image'));
            return redirect()->route('products.show', $product)->with('success', 'Review saved for moderation.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function reviewDelete($id)
    {
        try {
            Review::where('user_id', Auth::id())->findOrFail($id)->delete();
            return back()->with('success', 'Review removed.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function notifications()
    {
        try {
            return view('shop.notifications', ['notifications' => Commerce::owned(StoreRecord::in('notifications'))->latest()->get()]);
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function readNotifications()
    {
        try {
            Commerce::owned(StoreRecord::in('notifications'))->update(['read' => true]);
            return back()->with('success', 'Notifications marked read.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function help()
    {
        try {
            return view('shop.help');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function contact(Request $r)
    {
        try {
            $v = $r->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:190',
                'subject' => 'required|string|max:150',
                'message' => 'required|string|max:5000',
            ]);
            StoreRecord::in('support_messages')->create([
                'name' => $v['subject'],
                'data' => $v,
                'user_id' => Auth::id(),
                'session_key' => Commerce::owner(),
            ]);
            return back()->with('success', 'Your message has been saved for the store administrator.');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }

    public function newsletter(Request $r)
    {
        try {
            $v = $r->validate(['email' => 'required|email|max:190']);
            StoreRecord::in('support_messages')->create([
                'name' => 'Newsletter signup',
                'data' => ['email' => $v['email'], 'message' => 'Opted in to newsletters'],
                'user_id' => Auth::id(),
                'session_key' => Commerce::owner(),
            ]);
            return back()->with('success', 'You are on the list. Thank you!');
        } catch (\Throwable $__controllerException) {
            // Preserve existing error responses and transaction-owner rollback handling.
            throw $__controllerException;
        }
    }
}