<article class="product-card">
    <div class="product-image"><a class="image-open" href="{{ route('products.show', $p) }}"><img src="{{ $p->image }}"
                alt="{{ $p->name }}" loading="lazy"></a>@if($p->badge)<span class="badge">{{ $p->badge }}</span>@endif
        <form method="post" action="{{ route('wishlist.toggle', $p) }}">@csrf<button class="wish-btn"
                aria-label="Wishlist {{ $p->name }}">♡</button></form><a class="quick-view"
            href="{{ route('products.show', $p) }}">Quick view</a></div>
    <div class="product-info">
        <div class="small muted">{{ optional($categories->firstWhere('id', $p->category_id))->name }}</div><a
            class="product-name" href="{{ route('products.show', $p) }}">{{ $p->name }}</a>
        <div class="rating">★ {{ $p->rating ?: 'New' }} <span
                class="muted">{{ $p->reviews()->where('approved', true)->count() }} reviews</span></div>
        <div class="price-row">
            <div>
                <strong>{{ $money($p->price) }}</strong>@if($p->old_price > $p->price)<del>{{ $money($p->old_price) }}</del>@endif
            </div>
            <form method="post" action="{{ route('cart.add', $p) }}">@csrf<input type="hidden" name="quantity"
                    value="1"><input type="hidden" name="variant" value="{{ $p->options[0] ?? 'Default' }}"><button
                    class="add-small" {{ !$p->available_stock ? 'disabled' : '' }} aria-label="Add {{ $p->name }} to bag">+</button>
            </form>
        </div>@if(!$p->available_stock)<small>Out of stock</small>@endif
    </div>
</article>