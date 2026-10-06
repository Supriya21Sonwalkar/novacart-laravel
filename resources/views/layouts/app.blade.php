<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', $store['name'] ?? 'NovaCart')
        ·
        {{ $store['name'] ?? 'NovaCart' }}
    </title>

    <meta
        name="description"
        content="{{ $store['seo_description'] ?? 'Shop everyday essentials.' }}"
    >

    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="/store.css">
    <link rel="stylesheet" href="/laravel.css">
</head>

<body>

<div class="app {{ request()->is('admin*') ? 'admin-app' : '' }}">

    {{-- Announcement --}}
    <div class="announcement">
        <span>Thoughtful finds. Everyday prices.</span>

        <span>
            Free standard delivery above
            {{ $money(($store['free_threshold'] ?? 2999) * 100) }}
            · WELCOME10 saves 10%
        </span>

        <span>India / INR</span>
    </div>


    {{-- =====================================================
         HEADER
         
         Account page gets simplified header.
         All other pages keep the original header.
    ====================================================== --}}

    <header class="header">

        {{-- Logo --}}
        <a
            class="brand"
            href="{{ route('home') }}"
        >
            <span class="brand-mark">N</span>
            {{ $store['name'] ?? 'NovaCart' }}
            <span class="brand-dot">.</span>
        </a>


        {{-- Search --}}
        <form
            class="search"
            action="{{ route('shop') }}"
        >

            <span>⌕</span>

            <input
                name="q"
                aria-label="Search products"
                placeholder="Search for something good"
                value="{{ request('q') }}"
                list="products-search"
            >

            <datalist id="products-search">

                @foreach(
                    \App\Product::where('active', true)->take(100)->get()
                    as $suggestion
                )

                    <option value="{{ $suggestion->name }}">

                @endforeach

            </datalist>

            <button aria-label="Search">
                Search
            </button>

        </form>


        {{-- =================================================
             ACCOUNT PAGE HEADER
        ================================================== --}}

        @if(request()->is('account'))

            <div class="header-admin-area">

                {{-- Help --}}
                <a href="/help">
                    Help & support
                </a>


                @auth

                    @php
                        $adminDestination =
                            auth()->user()->canManage('dashboard')
                                ? '/admin'
                                : null;
                    @endphp


                    @foreach(array_keys(config('store.modules')) as $module)

                        @if(
                            !$adminDestination &&
                            auth()->user()->canManage($module)
                        )

                            @php
                                $adminDestination = '/admin/' . $module;
                            @endphp

                        @endif

                    @endforeach


                    @if($adminDestination)

                        <a
                            class="admin-link"
                            href="{{ $adminDestination }}"
                        >
                            Admin panel
                        </a>

                    @endif

                @endauth

            </div>


        {{-- =================================================
             NORMAL / LANDING PAGE HEADER
        ================================================== --}}

        @else

            <div class="header-actions">

                {{-- Account / Sign in --}}
                <a
                    href="{{ auth()->check()
                        ? route('account')
                        : route('login') }}"
                >
                    {{ auth()->check() ? 'Account' : 'Sign in' }}
                </a>


                {{-- Wishlist --}}
                @auth

                    <a
                        href="{{ route('wishlist') }}"
                        aria-label="Wishlist"
                    >
                        ♡
                    </a>

                @else

                    <a
                        href="{{ route('wishlist') }}"
                        aria-label="Wishlist"
                    >
                        ♡
                    </a>

                @endauth


                {{-- Bag --}}
                <a
                    href="{{ route('cart') }}"
                    aria-label="Shopping bag"
                >
                    Bag

                    <span class="count">
                        {{ $cartCount }}
                    </span>
                </a>


                {{-- Mobile menu --}}
                <button
                    class="mobile-menu"
                    data-menu
                    aria-label="Menu"
                >
                    ☰
                </button>

            </div>

        @endif

    </header>


    {{-- =====================================================
         NAVIGATION
         
         Account page: hidden
         All other pages: original navigation
    ====================================================== --}}

    @if(!request()->is('account'))

        <nav class="nav">

            {{-- Left navigation --}}
            <div>

                <a href="/">
                    Home
                </a>

                <a href="/shop">
                    Shop all
                </a>

                @foreach($categories as $cat)

                    <a
                        href="{{ route('shop', ['category' => $cat->id]) }}"
                    >
                        {{ $cat->name }}
                    </a>

                @endforeach

                <a
                    href="{{ route('shop', ['sort' => 'newest']) }}"
                >
                    New arrivals
                </a>

                <a
                    class="nav-deals"
                    href="{{ route('shop', ['offers' => 1]) }}"
                >
                    Offers
                </a>

            </div>


            {{-- Right navigation --}}
            <div>

                <a href="/help">
                    Help & support
                </a>

                @auth

                    @php
                        $adminDestination =
                            auth()->user()->canManage('dashboard')
                                ? '/admin'
                                : null;
                    @endphp


                    @foreach(array_keys(config('store.modules')) as $module)

                        @if(
                            !$adminDestination &&
                            auth()->user()->canManage($module)
                        )

                            @php
                                $adminDestination =
                                    '/admin/' . $module;
                            @endphp

                        @endif

                    @endforeach


                    @if($adminDestination)

                        <a
                            class="admin-link"
                            href="{{ $adminDestination }}"
                        >
                            Admin panel
                        </a>

                    @endif

                @endauth

            </div>

        </nav>

    @endif


    {{-- Flash messages --}}

    @if(session('success'))

        <div
            class="flash success"
            role="status"
        >
            {{ session('success') }}
        </div>

    @endif


    @if(session('notice'))

        <div class="flash notice">
            {{ session('notice') }}
        </div>

    @endif


    @if($errors->any())

        <div
            class="flash danger"
            role="alert"
        >

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- Page content --}}
    @yield('content')


    {{-- Footer --}}
    @unless(request()->is('admin*'))

        <footer>

            <div class="footer-grid">

                <div>

                    <a
                        class="brand"
                        href="/"
                    >
                        {{ $store['name'] ?? 'NovaCart' }}

                        <span class="brand-dot">
                            .
                        </span>
                    </a>

                    <p>
                        Good things for your everyday.
                    </p>

                    <span class="small">
                        A working ecommerce demo.
                    </span>

                </div>


                <div>

                    <h4>
                        Explore
                    </h4>

                    <a href="/shop">
                        Shop all
                    </a>

                    <a href="/shop?sort=newest">
                        New arrivals
                    </a>

                    <a href="/wishlist">
                        Wishlist
                    </a>

                </div>


                <div>

                    <h4>
                        Customer care
                    </h4>

                    <a href="/help">
                        Contact & FAQ
                    </a>

                    <a href="/orders">
                        Track your order
                    </a>

                    <a href="/help#returns">
                        Returns & refunds
                    </a>

                </div>


                <div>

                    <h4>
                        The store
                    </h4>

                    <a href="/help#about">
                        About us
                    </a>

                    <a href="/help#terms">
                        Terms & conditions
                    </a>

                    <a href="/help#privacy">
                        Privacy policy
                    </a>

                </div>

            </div>


            <div class="footer-bottom">

                <span>
                    © {{ date('Y') }}
                    {{ $store['name'] ?? 'NovaCart' }}
                </span>

                <span>
                    India · INR ₹
                </span>

                <span>
                    Test checkout · No real payments
                </span>

            </div>

        </footer>

    @endunless


</div>


<script>

    // Mobile navigation
    document
        .querySelectorAll('[data-menu]')
        .forEach(function (b) {

            b.addEventListener('click', function () {

                const nav =
                    document.querySelector('.nav');

                if (nav) {
                    nav.classList.toggle('mobile-open');
                }

            });

        });


    // Confirmation dialogs
    document
        .querySelectorAll('[data-confirm]')
        .forEach(function (f) {

            f.addEventListener('submit', function (e) {

                if (!confirm(f.dataset.confirm)) {
                    e.preventDefault();
                }

            });

        });


    // Product gallery
    document
        .querySelectorAll('[data-gallery]')
        .forEach(function (b) {

            b.addEventListener('click', function () {

                const image =
                    document.querySelector('#main-product-image');

                if (image) {
                    image.src = b.dataset.gallery;
                }

            });

        });

</script>


@yield('scripts')

</body>

</html>