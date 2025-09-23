<?php

declare(strict_types=1);

namespace Iutrace\Dashboard\Tests;

use Iutrace\Dashboard\Dashboard;
use Orchestra\Testbench\TestCase;
use Iutrace\Dashboard\DashboardServiceProvider;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DashboardServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dashboard.register_routes', true);
        $app['config']->set('dashboard.metrics_namespace', 'Iutrace\\Dashboard\\Tests\\Fixtures');
    }

    /** @test */
    public function it_can_get_metrics(): void
    {
        $dashboard = new Dashboard();
        $metrics = $dashboard->getMetrics();
        
        $this->assertIsArray($metrics);
    }

    /** @test */
    public function it_can_calculate_date_properties(): void
    {
        $properties = Dashboard::getDateFieldProperties('created_at', 'daily');
        
        $this->assertIsArray($properties);
        $this->assertArrayHasKey('groupBy', $properties);
        $this->assertArrayHasKey('dateFormat', $properties);
        $this->assertArrayHasKey('select', $properties);
    }
}
