<?php

declare(strict_types=1);

namespace Iutrace\Dashboard;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dashboard.php' => config_path('dashboard.php'),
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dashboard.php', 'dashboard');
        $this->app->singleton(Dashboard::class);

        if (config('dashboard.register_routes')) {
            Dashboard::routes();
        }
    }
}
