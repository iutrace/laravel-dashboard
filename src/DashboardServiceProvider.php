<?php

namespace Iutrace\Dashboard;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DashboardServiceProvider extends ServiceProvider
{
    public $metricsPath;

    public $metrics = [];

    public function boot(){
        $this->registerRoutes();

        $this->metricsPath = App::basePath('Metrics');

        /* @var $file SplFileInfo */
        foreach (new RecursiveIteratorIterator (new RecursiveDirectoryIterator ($this->metricsPath)) as $file)
        {
            $this->metrics[] = $file->getPathname();
        }
    }

    protected function registerRoutes(){
        Route::get('dashboard/data', [DashboardController::class, 'data']);
    }
}