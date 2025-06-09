<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\OlxPriceFetcherService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the OlxPriceFetcherService as a singleton
        $this->app->singleton(OlxPriceFetcherService::class, fn ($app) => new OlxPriceFetcherService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
