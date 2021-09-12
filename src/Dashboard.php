<?php
declare(strict_types=1);

namespace Iutrace\Dashboard;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class Dashboard
{
    public function getMetrics(): array
    {
        $metricsPath = config('dashboard.metrics_path');

        if (!file_exists($metricsPath))
            return [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator ($metricsPath));
        $metrics = [];

        /* @var $file SplFileInfo */
        foreach (new RegexIterator($files, '/\.php$/') as $file)
        {
            $metrics[] = $file->getPathname();
        }

        return $metrics;
    }
}
