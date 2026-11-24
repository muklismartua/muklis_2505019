<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Models\Category;
use App\Models\Customer;
use App\Models\HutangSupplier;
use App\Models\Piutang;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Observers\MasterDataActivityObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $overdueCount = 0;

            if ($user?->canAny(['piutang.lihat', 'piutang.kelola'])) {
                $query = Piutang::query()
                    ->where('status', Piutang::STATUS_UNPAID)
                    ->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', today());

                if ($user->hasRole('kasir') && ! $user->hasRole('admin') && ! $user->hasRole('owner')) {
                    $query->whereHas('sale', fn ($saleQuery) => $saleQuery->where('user_id', $user->id));
                }

                $overdueCount = $query->count();
            }

            $view->with('overduePiutangCount', $overdueCount);

            $overdueSupplierDebtCount = $user?->canAny(['hutang.lihat', 'hutang.kelola'])
                ? HutangSupplier::query()
                    ->where('status', HutangSupplier::STATUS_UNPAID)
                    ->whereNotNull('jatuh_tempo')
                    ->whereDate('jatuh_tempo', '<', today())
                    ->count()
                : 0;

            $view->with('overdueSupplierDebtCount', $overdueSupplierDebtCount);
        });
    }
}
