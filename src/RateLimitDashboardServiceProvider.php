<?php

namespace Dev\RateLimitDashboard;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Dev\RateLimitDashboard\Http\Middleware\TrackRateLimits;

class RateLimitDashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rate-limit-dashboard.php', 'rate-limit-dashboard'
        );
    }

    public function boot(Kernel $kernel): void
    {
        $kernel->pushMiddleware(TrackRateLimits::class);

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rate-limit-dashboard');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/rate-limit-dashboard.php' => config_path('rate-limit-dashboard.php'),
            ], 'rate-limit-dashboard-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/rate-limit-dashboard'),
            ], 'rate-limit-dashboard-views');
        }
    }
}