@extends('layouts.app')@section('title', $product->name)
@section('content')
    <main>
        <div class="breadcrumb"><a href="/shop">Shop</a> / {{ $product->name }}</div>
        <div class="detail-layout">
            <div>
                <div class="detail-image"><img id="main-product-image" src="{{ $product->image }}"
                        alt="{{ $product->name }}"></div>
                <div class="thumbnails"><button data-gallery="{{ $product->image }}"><img src="{{ $product->image }}"
                            alt="Main product image"></button>@foreach($product->images as $image)<button
                                data-gallery="{{ $image->url }}"><img src="{{ $image->url }}"
                            alt="Product gallery image"></button>@endforeach</div>
            </div>
            <div class="detail-copy"><span
                    class="eyebrow muted">{{ optional(\App\StoreRecord::in('brands')->find($product->brand_id))->name }}</span>
                <h1>{{ $product->name }}</h1>
                <div class="rating">★ {{ $product->rating ?: 'New' }} · {{ $reviews->count() }} reviews</div>
                <div class="detail-price">{{ $money($product->price) }}
                    @if($product->old_price > $product->price)<del>{{ $money($product->old_price) }}</del>@endif</div>
                <p>{{ $product->description }}</p><span
                    class="{{ $product->stock ? 'success' : 'danger' }}">{{ $product->stock ? $product->stock . ' available' : 'Out of stock' }}</span>
                <form method="post" action="{{ route('cart.add', $product) }}">@csrf<label>Choose a variant<select
                            name="variant">@foreach($product->options as $v)
                            <option>{{ $v }}</option>@endforeach
                        </select></label>
                    <div class="detail-buy"><label>Quantity<input name="quantity" type="number" min="1"
                                max="{{ max(1, $product->stock) }}" value="1" required></label><button class="primary" {{ !$product->stock ? 'disabled' : '' }}>Add to bag</button><button class="outline" name="buy_now"
                            value="1" {{ !$product->stock ? 'disabled' : '' }}>Buy now</button></div>
                </form>
                <form method="post" action="{{ route('wishlist.toggle', $product) }}">@csrf<button class="outline full">♡ Add
                        / remove from wishlist</button></form>
                <div class="detail-promises"><span>Delivery in 3–5 days</span><span>7-day return requests</span></div>
                <details open>
                    <summary>Specifications & details</summary>
                    <p>{{ $product->specifications }}</p><small>SKU: {{ $product->sku }}</small>
                </details><button class="text-link"
                    onclick="navigator.clipboard.writeText(window.location.href).then(()=>this.textContent='Link copied')">Share
                    product</button>
            </div>
        </div>
        <section>
            <div class="section-head">
                <h2>Reviews & ratings</h2><a class="outline" href="{{ route('reviews.new', $product) }}">Write a review</a>
            </div>@forelse($reviews as $r)
                <article class="review">
                    <div class="rating">{{ str_repeat('★', $r->rating) }} · {{ $r->user->name }}</div>
                    <h3>{{ $r->title }}</h3>
                    <p>{{ $r->text }}</p>@if($r->image)<img src="{{ $r->image }}" alt="Customer review">@endif @if($r->response)
                    <blockquote>Store reply: {{ $r->response }}</blockquote>@endif
            </article>@empty<p class="muted">Be the first to share your thoughts.</p>@endforelse
        </section>@if($related->count())
            <section>
                <div class="section-head">
                    <h2>You might also like</h2>
                </div>
                <div class="product-grid">@foreach($related as $p)@include('shop.card')@endforeach</div>
        </section>@endif
</main>@endsection