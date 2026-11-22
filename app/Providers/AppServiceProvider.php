<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Observers\MasterDataActivityObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Category::class, Unit::class, Supplier::class, Customer::class, Product::class] as $model) {
            $model::observe(MasterDataActivityObserver::class);
        }

        Event::listen(Login::class, LogSuccessfulLogin::class);
    }
}
