<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\InvoiceServiceInterface;
use App\Services\InvoiceService;
use App\Contracts\Services\ProductServiceInterface;
use App\Services\ProductService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            InvoiceServiceInterface::class,
            InvoiceService::class
        );
        $this->app->bind(
            ProductServiceInterface::class,
            ProductService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = app()->environment('testing') ? 500 : 60;

            return Limit::perMinute($limit)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
