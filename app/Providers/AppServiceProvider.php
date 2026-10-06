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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Support\Facades\View::composer('*',function($view){
            $view->with('money',function($value){return \App\Services\Commerce::money($value);});
            $view->with('store',\App\Services\Commerce::settings());
            $view->with('categories',\App\StoreRecord::in('categories')->where('active',true)->get()->sortBy(function($c){return $c->data['position']??0;}));
            $view->with('cartCount',\App\Services\Commerce::items()->sum('quantity'));
        });
    }
}
