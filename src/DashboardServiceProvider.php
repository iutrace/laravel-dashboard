<?php
declare(strict_types=1);

namespace Iutrace\Dashboard;

use Illuminate\Support\ServiceProvider;
use Iutrace\Dashboard\Http\DashboardController;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();

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
    }

    protected function registerRoutes()
    {
        if (function_exists('env') && env('REGISTER_WORKER_ROUTES', true))
            $this->app['router']->get('dashboard/data', [DashboardController::class, 'data'])->middleware('auth');
    }
}
