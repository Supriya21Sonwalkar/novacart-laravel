<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Keep the existing failure guarantees while using Laravel's DB::transaction API.
        \Illuminate\Database\Connection::resolverFor('mysql', function ($pdo, $database, $prefix, $config) {
            return new \App\Database\MySqlConnection($pdo, $database, $prefix, $config);
        });

        \Illuminate\Database\Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
    return new \App\Database\SQLiteConnection($pdo, $database, $prefix, $config);
});
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            // Error pages must render even when the database is unavailable.
            if (strpos($view->name(), 'errors') === 0) {
                return;
            }

            $view->with('money', function ($value) {
                return \App\Services\Commerce::money($value);
            });

            $view->with('store', \App\Services\Commerce::settings());

            $view->with(
                'categories',
                \App\StoreRecord::in('categories')
                    ->where('active', true)
                    ->whereNull('parent_id')
                    ->get()
                    ->sortBy(function ($c) {
                        return $c->data['position'] ?? 0;
                    })
            );

            $view->with(
                'subcategories',
                \App\Subcategory::where('active', true)
                    ->orderBy('name')
                    ->get()
            );

            $view->with(
                'cartCount',
                \App\Services\Commerce::items()->sum('quantity')
            );
            $notificationCount = 0;

if (\Illuminate\Support\Facades\Auth::check()) {
    $notificationCount = \App\StoreRecord::in('notifications')
        ->where('user_id', \Illuminate\Support\Facades\Auth::id())
        ->where('read', false)
        ->count();
}

$view->with('notificationCount', $notificationCount);

            // Wishlist count for the currently authenticated user.
            $wishlistCount = 0;

            if (\Illuminate\Support\Facades\Auth::check()) {
                $wishlist = \Illuminate\Support\Facades\DB::table('wishlists')
                    ->where(
                        'user_id',
                        \Illuminate\Support\Facades\Auth::id()
                    )
                    ->first();

                if ($wishlist) {
                    $wishlistCount = \Illuminate\Support\Facades\DB::table('wishlist_items')
                        ->where('wishlist_id', $wishlist->id)
                        ->count();
                }
            }

            $view->with('wishlistCount', $wishlistCount);
        });
    }
}
