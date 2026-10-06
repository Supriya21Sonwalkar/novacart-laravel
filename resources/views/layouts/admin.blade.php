<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('admin_title', 'Dashboard') · {{ $store['name'] ?? 'NovaCart' }}
    </title>

    <meta
        name="description"
        content="{{ $store['seo_description'] ?? 'Shop everyday essentials.' }}"
    >

    <link
        rel="icon"
        href="/favicon.svg"
    >

    {{-- Existing store styles --}}
    <link
        rel="stylesheet"
        href="/store.css"
    >

    <link
        rel="stylesheet"
        href="/laravel.css"
    >


    {{-- ================= ADMIN DROPDOWN CSS ================= --}}
    <style>

        .admin-header-actions {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .admin-dropdown {
            position: relative;
        }

        .admin-dropdown-btn {
            background: none;
            border: none;
            padding: 0;

            font: inherit;
            color: inherit;

            cursor: pointer;

            display: flex;
            align-items: center;
            gap: 6px;
        }

        .admin-arrow {
            font-size: 14px;
            transition: transform 0.2s ease;
        }

        .admin-dropdown.open .admin-arrow {
            transform: rotate(180deg);
        }

        .admin-dropdown-menu {
            display: none;

            position: absolute;

            top: calc(100% + 12px);
            right: 0;

            min-width: 130px;

            background: #ffffff;

            border: 1px solid #e1e6e6;

            border-radius: 8px;

            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.10);

            padding: 6px;

            z-index: 9999;
        }

        .admin-dropdown.open .admin-dropdown-menu {
            display: block;
        }

        .admin-logout-btn {
            width: 100%;

            border: none;
            background: transparent;

            padding: 10px 14px;

            text-align: left;

            font: inherit;

            color: #111827;

            cursor: pointer;

            border-radius: 6px;
        }

        .admin-logout-btn:hover {
            background: #f3f5f5;
        }

    </style>

</head>


<body>

<div class="app admin-app">


    {{-- ======================================================
         ANNOUNCEMENT BAR
    ======================================================= --}}

    <div class="announcement">

        <span>
            Thoughtful finds. Everyday prices.
        </span>

        <span>
            Free standard delivery above
            {{ $money(($store['free_threshold'] ?? 2999) * 100) }}
            · WELCOME10 saves 10%
        </span>

        <span>
            India / INR
        </span>

    </div>



    {{-- ======================================================
         ADMIN HEADER
    ======================================================= --}}

    <header class="header">


        {{-- Logo --}}
        <a
            class="brand"
            href="{{ route('home') }}"
        >

            <span class="brand-mark">
                N
            </span>

            {{ $store['name'] ?? 'NovaCart' }}

            <span class="brand-dot">
                .
            </span>

        </a>



        {{-- Search --}}
        <form
            class="search"
            action="{{ route('shop') }}"
        >

            <span>
                ⌕
            </span>

            <input
                name="q"
                aria-label="Search products"
                placeholder="Search for something good"
                value="{{ request('q') }}"
                list="admin-products-search"
            >

            <datalist id="admin-products-search">

                @foreach(
                    \App\Product::where('active', true)
                        ->take(100)
                        ->get()
                    as $suggestion
                )

                    <option value="{{ $suggestion->name }}">

                @endforeach

            </datalist>

            <button aria-label="Search">
                Search
            </button>

        </form>



        {{-- ==================================================
             ADMIN HEADER ACTIONS
        =================================================== --}}

        <div class="admin-header-actions">


            {{-- Help --}}
            <a href="/help">
                Help & support
            </a>


            {{-- Admin dropdown --}}
            @auth

                @php
                    $adminDestination =
                        auth()->user()->canManage('dashboard')
                            ? '/admin'
                            : null;
                @endphp


                @foreach(array_keys(config('store.modules')) as $module)

                    @if(
                        !$adminDestination
                        &&
                        auth()->user()->canManage($module)
                    )

                        @php
                            $adminDestination =
                                '/admin/' . $module;
                        @endphp

                    @endif

                @endforeach


                @if($adminDestination)

                    <div class="admin-dropdown">


                        <button
                            type="button"
                            class="admin-dropdown-btn"
                            id="adminDropdownBtn"
                        >

                            Admin panel

                            <span class="admin-arrow">
                                ⌄
                            </span>

                        </button>



                        {{-- Dropdown --}}
                        <div
                            class="admin-dropdown-menu"
                            id="adminDropdownMenu"
                        >

                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="admin-logout-btn"
                                >
                                    Logout
                                </button>

                            </form>

                        </div>

                    </div>

                @endif

            @endauth

        </div>

    </header>



    {{-- ======================================================
         ADMIN PAGE
    ======================================================= --}}

    <div class="admin-layout">


        {{-- ==================================================
             SIDEBAR
        =================================================== --}}

        <aside class="admin-sidebar">


            <span class="eyebrow muted">
                STORE MANAGEMENT
            </span>


            {{-- Dashboard --}}
            @if(auth()->user()->canManage('dashboard'))

                <a
                    href="/admin"
                    class="{{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}"
                >
                    Dashboard
                </a>

            @endif



            {{-- Admin modules --}}
            @foreach(config('store.modules') as $key => $config)

                @if(auth()->user()->canManage($key))

                    <a
                        class="{{ request()->route('module') === $key ? 'selected' : '' }}"
                        href="{{ route('admin.index', $key) }}"
                    >
                        {{ $config['title'] }}
                    </a>

                @endif

            @endforeach



            {{-- Back to storefront --}}
            <a
                class="back-store"
                href="/"
            >
                Back to storefront
            </a>

        </aside>



        {{-- ==================================================
             MAIN ADMIN CONTENT
        =================================================== --}}

        <main class="admin-main">


            {{-- Admin page heading --}}
            <div class="admin-top">

                <div>

                    <span class="eyebrow muted">

                        {{ $store['name'] ?? 'NovaCart' }}

                        / ADMIN

                    </span>


                    <h1>
                        @yield('admin_title', 'Dashboard')
                    </h1>

                </div>



                {{-- Notifications --}}
                <a
                    class="outline"
                    href="/notifications"
                >
                    Notifications
                </a>

            </div>



            {{-- Dashboard / module content --}}
            @yield('admin_content')


        </main>

    </div>



    {{-- ======================================================
         FLASH MESSAGES
    ======================================================= --}}

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



    {{-- ======================================================
         ADMIN DROPDOWN JAVASCRIPT
    ======================================================= --}}

    <script>

        const adminDropdownBtn =
            document.getElementById('adminDropdownBtn');

        const adminDropdown =
            document.querySelector('.admin-dropdown');


        if (adminDropdownBtn && adminDropdown) {


            // Open / close dropdown
            adminDropdownBtn.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    adminDropdown.classList.toggle('open');

                }
            );


            // Close when clicking outside
            document.addEventListener(
                'click',
                function (event) {

                    if (
                        !adminDropdown.contains(
                            event.target
                        )
                    ) {

                        adminDropdown.classList.remove(
                            'open'
                        );

                    }

                }
            );

        }

    </script>


    @yield('scripts')


</div>

</body>

</html>