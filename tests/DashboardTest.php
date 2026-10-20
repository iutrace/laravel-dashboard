<?php

declare(strict_types=1);

namespace Iutrace\Dashboard\Tests;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Iutrace\Dashboard\Dashboard;
use Iutrace\Dashboard\Metric;
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
    public function it_only_reads_rows_inside_the_charted_periods(): void
    {
        // SQLite lacks the MySQL date functions the grouping uses.
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('YEAR', fn ($date) => (int) substr($date, 0, 4));
        $pdo->sqliteCreateFunction('MONTH', fn ($date) => (int) substr($date, 5, 2));
        $pdo->sqliteCreateFunction('DATE_FORMAT', fn ($date, $format) => Carbon::parse($date)->format(str_replace(['%Y', '%m'], ['Y', 'm'], $format)));

        Schema::create('events', function (Blueprint $table) {
            $table->dateTime('created_at');
        });
        DB::table('events')->insert([
            ['created_at' => '2025-09-30 23:59:59'],
            ['created_at' => '2025-10-01 00:00:00'],
            ['created_at' => '2026-10-31 23:59:59'],
            ['created_at' => '2026-11-01 00:00:00'],
        ]);

        $metric = new class () extends Metric {
            public function query(Request $request)
            {
                return DB::table('events')->selectRaw('COUNT(*) as value');
            }

            public function name(): string
            {
                return 'events';
            }
        };

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $toDate = Carbon::parse('2026-10-20')->endOfDay();
        $data = Dashboard::generateChartData($metric, new Request(), Dashboard::calculateFromDate($toDate), $toDate);

        $this->assertStringContainsString('where "created_at" between', end($queries));
        $this->assertSame(1.0, $data['10/2025']);
        $this->assertSame(1.0, $data['10/2026']);
        $this->assertSame(2.0, array_sum($data));
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
