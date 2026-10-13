
@extends('layouts.app')
@section('title', 'The collection')

@section('content')
    @php
        $mainCategories = $categories->filter(function ($category) {
            return empty($category->data['parent_id']);
        });

        $selectedCategory = (string) request('category', '');
        $selectedSubcategory = (string) request('subcategory', '');
    @endphp

    <main>
        <div class="breadcrumb">Home / Shop</div>

        <div class="page-heading">
            <div>
                <span class="eyebrow muted">EVERYDAY, ELEVATED</span>
                <h1>The collection</h1>
                <p>Considered essentials, picked for the way you live.</p>
            </div>
            <span class="muted">{{ $products->total() }} products</span>
        </div>

        <div class="catalog">
            <form action="/shop" method="GET" class="filters">
                <h3>Filters <a href="/shop">Reset</a></h3>

                <label>
                    Search
                    <input name="q" value="{{ request('q') }}">
                </label>

                <label>
                    Category
                    <select name="category" id="main-category">
                        <option value="">All categories</option>
                        @foreach($mainCategories as $c)
                            <option value="{{ $c->id }}"
                                {{ $selectedCategory === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label id="subcategory-field"
                    style="{{ $selectedCategory !== '' ? '' : 'display:none;' }}">
                    Subcategory
                    <select name="subcategory" id="subcategory">
                        <option value="">All subcategories</option>
                                        @foreach($subcategories as $c)
                                                <option
                                                    value="{{ $c->id }}"
                                                    data-parent="{{ $c->category_id }}"
                                                    {{ $selectedSubcategory === (string) $c->id ? 'selected' : '' }}>
                                                    {{ $c->name }}
                                                </option>
                                        @endforeach
                    </select>
                </label>

                <label>
                    Brand
                    <select name="brand">
                        <option value="">All brands</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}"
                                {{ request('brand') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Maximum price (₹)
                    <input type="number" min="0" name="max"
                        value="{{ request('max') }}">
                </label>

                <label>
                    Customer rating
                    <select name="rating">
                        <option value="">All ratings</option>
                        @foreach([3, 4, 5] as $r)
                            <option value="{{ $r }}"
                                {{ request('rating') == $r ? 'selected' : '' }}>
                                {{ $r }} stars and above
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Sort by
                    <select name="sort">
                        @foreach([
                            'featured' => 'Featured',
                            'price_low' => 'Price: low to high',
                            'price_high' => 'Price: high to low',
                            'newest' => 'Newest',
                            'popular' => 'Popularity'
                        ] as $key => $value)
                            <option value="{{ $key }}"
                                {{ request('sort', 'featured') === $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="check-label">
                    <input type="checkbox" name="stock" value="1"
                        {{ request('stock') ? 'checked' : '' }}>
                    In stock only
                </label>

                <label class="check-label">
                    <input type="checkbox" name="offers" value="1"
                        {{ request('offers') ? 'checked' : '' }}>
                    Discounted products
                </label>

                <button class="primary full">Apply filters</button>
            </form>

            <div>
                @if($products->count())
                    <div class="product-grid">
                        @foreach($products as $p)
                            @include('shop.card')
                        @endforeach
                    </div>

                    {{ $products->links() }}
                @else
                    <div class="empty">
                        <h2>No matching products</h2>
                        <p>Try another search or reset the filters.</p>
                        <a class="primary" href="/shop">Reset filters</a>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    @parent
    <script>
        (function () {
            var mainCategory = document.getElementById('main-category');
            var subcategory = document.getElementById('subcategory');
            var subcategoryField = document.getElementById('subcategory-field');

            if (!mainCategory || !subcategory || !subcategoryField) {
                return;
            }

            var allOptions = Array.prototype.slice.call(subcategory.options);

            function updateSubcategories(resetSelection) {
                var parentId = mainCategory.value;
                var previousValue = resetSelection ? '' : subcategory.value;
                var hasChildren = false;

                subcategory.innerHTML = '';

                var allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = 'All subcategories';
                subcategory.appendChild(allOption);

                allOptions.forEach(function (option) {
                    if (!option.value) {
                        return;
                    }

                    if (option.getAttribute('data-parent') === parentId) {
                        var copy = option.cloneNode(true);
                        copy.selected = copy.value === previousValue;
                        subcategory.appendChild(copy);
                        hasChildren = true;
                    }
                });

                subcategoryField.style.display =
                    parentId && hasChildren ? '' : 'none';

                if (!hasChildren || !parentId) {
                    subcategory.value = '';
                }
            }

            mainCategory.addEventListener('change', function () {
                updateSubcategories(true);
            });

            updateSubcategories(false);
        })();
    </script>
@endsection
