
@extends('layouts.admin')

@section('admin_title', ($item ? 'Edit ' : 'Add ') . ($module === 'products' ? 'product' : strtolower($c['title'])))

@section('admin_content')

<form class="admin-card panel-form" method="post" enctype="multipart/form-data"
    action="{{ $item ? route('admin.update', [$module, $item->id]) : route('admin.store', $module) }}">

    @csrf
    @if($item)
        @method('PUT')
    @endif

    <div class="form-grid">
        @foreach($c['fields'] as $field)

            @if($module === 'users' && $item && in_array($field, ['password', 'password_confirmation']))
                @continue
            @endif

            @php
                $value = old($field, $values[$field] ?? ($field === 'product_id' ? request('product_id') : ''));
                $categoryType = old(
                    'category_type',
                    $module === 'categories' && !empty($values['parent_id'])
                        ? 'subcategory'
                        : 'main'
                );
            @endphp

            <label class="{{ in_array($field, ['description', 'specifications', 'permissions', 'about', 'terms', 'privacy', 'returns']) ? 'span2' : '' }}">
                {{ $module === 'coupons' && $field === 'name' ? 'Coupon code' : ucwords(str_replace('_', ' ', $field)) }}

                @if(in_array($field, ['active', 'featured', 'approved']))

                    <span class="check-label">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ $value ? 'checked' : '' }}>
                        Enabled
                    </span>

                @elseif($field === 'permissions')

                    <div class="permission-grid">
                        @foreach(array_merge(['dashboard'], array_keys(config('store.modules'))) as $permission)
                            <label class="check-label">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                    {{ in_array($permission, is_array($value) ? $value : []) ? 'checked' : '' }}>
                                {{ $permission === 'dashboard' ? 'Dashboard' : config('store.modules.'.$permission.'.title') }}
                            </label>
                        @endforeach
                    </div>

                @elseif(in_array($field, ['category_id', 'subcategory_id', 'brand_id', 'parent_id', 'role_id', 'product_id', 'user_id', 'vendor_id']))

                    @php
                        $table = [
                            'category_id' => 'categories',
                            'subcategory_id' => 'subcategories',
                            'brand_id' => 'brands',
                            'parent_id' => 'categories',
                            'role_id' => 'roles',
                            'product_id' => 'products',
                            'user_id' => 'users',
                            'vendor_id' => 'vendors',
                        ][$field];

                        $options = $table === 'products'
                            ? \App\Product::all()
                            : ($table === 'subcategories'
                                ? \App\Subcategory::where('active', true)->orderBy('name')->get()
                                : ($table==='users'?\App\User::where('active',true)->whereIn('role_id',\App\StoreRecord::in('roles')->where('name','vendor')->pluck('id'))->get():\App\StoreRecord::in($table)->get()));

                        if ($field==='role_id'&&!config('store_management.vendor_portal')) { $options=$options->reject(function($role){return $role->name==='vendor';}); }
                        if ($field === 'category_id') {
                            $options = $options->filter(function ($category) {
                                return empty($category->parent_id)
                                    && empty($category->data['parent_id']);
                            })->values();
                        }

                        if ($field === 'subcategory_id') {
                            $selectedCategory = old('category_id', $values['category_id'] ?? request('category_id'));
                            if ($selectedCategory) {
                                $options = $options->where('category_id', $selectedCategory);
                            }
                        }

                       
if ($module === 'categories' && $field === 'parent_id') {
    $options = $options->filter(function ($category) use ($item) {
        $parentId = $category->parent_id ?: ($category->data['parent_id'] ?? null);

        return (!$item || (int) $category->id !== (int) $item->id)
            && empty($parentId);
    })->values();
}

                    @endphp

                    @if($module === 'categories' && $field === 'parent_id')

                        <select name="category_type" id="category-type">
                            <option value="main" {{ $categoryType === 'main' ? 'selected' : '' }}>Main category</option>
                            <option value="subcategory" {{ $categoryType === 'subcategory' ? 'selected' : '' }}>Subcategory</option>
                        </select>
                        <small>Choose whether this is a new top-level category or a subcategory.</small>

                        <div id="category-parent-field">
                            <span>Parent category</span>
                            <select name="parent_id" id="category-parent">
                                <option value="">Choose parent category</option>
                            @foreach($options as $option)
                                <option value="{{ $option->id }}"
                                    {{ (string) $value === (string) $option->id ? 'selected' : '' }}>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                            </select>

                            <small>Subcategories will be grouped under this category.</small>
                        </div>

                    @else

                        <select
                            name="{{ $field }}"
                            @if($module === 'products' && $field === 'category_id') id="product-category" @endif
                            @if($module === 'products' && $field === 'subcategory_id') id="product-subcategory" @endif
                            {{ in_array($field, ['role_id', 'product_id']) ? 'required' : '' }}
                        >
                            <option value="">Choose {{ str_replace('_id', '', $field) }}</option>
                            @foreach($options as $option)
                                <option
                                    value="{{ $option->id }}"
                                    @if($field === 'subcategory_id') data-category="{{ $option->category_id }}" @endif
                                    {{ (string) $value === (string) $option->id ? 'selected' : '' }}>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>

                    @endif

                @elseif($field === 'delivery_type')

                    <select name="delivery_type">
                        @foreach(['standard' => 'Standard', 'fresh' => 'Fresh — same-day delivery', 'dairy' => 'Dairy — within 60 minutes'] as $type => $label)
                            <option value="{{ $type }}" {{ ($value ?: 'standard') === $type ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                @elseif($field === 'delivery_minutes')

                    <input type="number" name="delivery_minutes" min="1" max="240" required value="{{ $value ?: 60 }}">
                    <small>Preparation and delivery time in minutes. Dairy: maximum 60 minutes.</small>

                @elseif($field === 'type' && $module === 'coupons')

                    <select name="type">
                        <option value="percentage" {{ $value === 'percentage' ? 'selected' : '' }}>Percentage</option>
                        <option value="fixed" {{ $value === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                    </select>

                @elseif(in_array($field, ['description', 'specifications', 'response', 'about', 'terms', 'privacy', 'returns']))

                    <textarea name="{{ $field }}">{{ $value }}</textarea>

                @else

                    <input
                        name="{{ $field }}"
                        value="{{ is_array($value) ? json_encode($value) : $value }}"
                        {{ in_array($field, ['name', 'sku', 'price', 'stock', 'value', 'charge', 'adjustment', 'reason']) ? 'required' : '' }}
                        type="{{ in_array($field, ['price', 'old_price', 'stock', 'value', 'minimum', 'maximum', 'limit', 'charge', 'free_threshold', 'tax', 'position', 'adjustment', 'commission_percent']) ? 'number' : (in_array($field, ['starts', 'expires']) ? 'date' : (in_array($field, ['email', 'support_email']) ? 'email' : (in_array($field, ['password', 'password_confirmation']) ? 'password' : 'text'))) }}"
                        step="{{ in_array($field, ['stock', 'limit', 'position', 'adjustment']) ? '1' : 'any' }}"
                    >

                    @if(in_array($field, ['price', 'old_price', 'charge', 'minimum', 'maximum', 'free_threshold']))
                        <small>Enter amount in rupees.</small>
                    @endif

                    @if($field === 'gallery')
                        <small>Comma-separated image URLs.</small>
                    @endif

                    @if($field === 'variants')
                        <small>Comma-separated options, e.g. Black / M, Black / L.</small>
                    @endif

                @endif

                @if($errors->has($field))
                    <span class="aw-inline-error" role="alert">{{ $errors->first($field) }}</span>
                @endif
            </label>

        @endforeach
    </div>

    @if($module === 'products')
        <label class="upload">
            Upload product image
            <input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG, WebP · Maximum 5 MB</small>

            @if($values['image'] ?? null)
                <img src="{{ $values['image'] }}" alt="Product preview">
            @endif
        </label>
    @endif

    @if($module === 'reviews')
        <p>{{ optional($item->product)->name }} · {{ $item->rating }} stars</p>
        <blockquote>{{ $item->text }}</blockquote>
    @endif

    <div class="editor-footer">
        <a class="outline" href="{{ route('admin.index', $module) }}">Cancel</a>
        <button class="primary">Save changes</button>
    </div>

</form>

@endsection

@if($module === 'categories')
    @section('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var type = document.getElementById('category-type');
                var parentField = document.getElementById('category-parent-field');
                var parent = document.getElementById('category-parent');

                function updateCategoryType() {
                    var isSubcategory = type.value === 'subcategory';
                    parentField.style.display = isSubcategory ? '' : 'none';
                    parent.required = isSubcategory;

                    if (!isSubcategory) {
                        parent.value = '';
                    }
                }

                type.addEventListener('change', updateCategoryType);
                updateCategoryType();
            });
        </script>
    @endsection
@endif

@if($module === 'products')
    @section('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var category = document.getElementById('product-category');
                var subcategory = document.getElementById('product-subcategory');
                if (!category || !subcategory) {
                    return;
                }

                function filterSubcategories() {
                    var categoryId = category.value;
                    Array.prototype.forEach.call(subcategory.options, function (option, index) {
                        if (index === 0) {
                            option.hidden = false;
                            return;
                        }
                        option.hidden = option.getAttribute('data-category') !== categoryId;
                    });

                    if (!subcategory.value ||
                        subcategory.selectedOptions[0].getAttribute('data-category') !== categoryId) {
                        subcategory.value = '';
                    }
                    subcategory.disabled = !categoryId;
                }

                category.addEventListener('change', filterSubcategories);
                filterSubcategories();
            });
        </script>
    @endsection
@endif
