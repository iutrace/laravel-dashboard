<?php

declare(strict_types=1);

namespace Iutrace\Dashboard;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use stdClass;

class Dashboard
{
    public function getMetrics(): array
    {
        $namespace = config('dashboard.metrics_namespace');
        $namespace .= '\\';

        return array_filter(get_declared_classes(), function ($item) use ($namespace) {
            return substr($item, 0, strlen($namespace)) === $namespace;
        });
    }

    public static function routes(): void
    {
        $options = [
            'namespace' => '\Iutrace\Dashboard\Http',
            'middleware' => ['web', 'auth'],
        ];

        Route::group($options, function ($router) {
            $router->get('/dashboard/data', [
                'uses' => 'DashboardController@data',
                'as' => 'iutrace.dashboard.data',
            ]);
        });
    }

    /** Get date field properties for query group and select
     * @param String $dateField
     * @param String|null $period
     * @return string[]
     */
    public static function getDateFieldProperties(String $dateField, String $period = null): array
    {
        switch ($period) {
            case 'daily':
                $dateFormat = '\'%Y-%m-%d\'';
                $groupBy = 'YEAR(' . $dateField . '),MONTH(' . $dateField . '),DAY(' . $dateField . ')';

                break;

            case 'weekly':
                $dateFormat = '\'%Y-%v\'';
                $groupBy = 'DATE_FORMAT(' . $dateField . ', ' . $dateFormat . ')';

                break;

            default:
                $dateFormat = '\'%Y-%m\'';
                $groupBy = 'YEAR(' . $dateField . '),MONTH(' . $dateField . ')';

                break;
        }

        return [
            'groupBy' => $groupBy,
            'dateFormat' => $dateFormat,
            'select' => 'DATE_FORMAT(' . $dateField . ', ' . $dateFormat . ')',
        ];
    }

    /** Adds date select and group to the query
     *
     * @param mixed $query The query to be modified
     * @param String $dateField The name of the field in the sql query
     * @param String|null $outputField Name of the alias of the field
     * @param String|null $period The period of the data, daily, weekly or monthly
     *
     * @return void
     */
    public static function addDateToQuery($query, String $dateField, String $outputField = null, String $period = null)
    {
        $properties = self::getDateFieldProperties($dateField, $period);

        $query->addSelect(DB::raw($properties['select'] . ($outputField != null ? ' as ' . $outputField : '')));
        $query->groupBy(DB::raw($properties['groupBy']));
    }

    /** Calculates from date with the given period to reach the target date
     * @param Carbon $toDate
     * @param String|null $period
     * @return Carbon
     */
    public static function calculateFromDate(Carbon $toDate, String $period = null): Carbon
    {
        switch ($period) {
            case 'daily':
                $fromDate = $toDate->copy()->subtract(12, 'days')->startOfDay();

                break;

            case 'weekly':
                $fromDate = $toDate->copy()->subtract(12, 'weeks')->startOfWeek();

                break;

            default:
                $fromDate = $toDate->copy()->subtract(12, 'months')->startOfMonth();

                break;
        }

        return $fromDate;
    }

    public static function formatData(Collection $data, Carbon $fromDate, Carbon $toDate, String $period = null, $defaultValue): array
    {
        $currentDate = clone $fromDate;
        switch ($period) {
            case 'daily':
                $period = 'days';
                $format = 'Y-m-d';
                $outputFormat = 'd/m/Y';

                break;
            case 'weekly':
                $period = 'weeks';
                $format = 'Y-W';
                $outputFormat = 'd/m/Y';

                break;
            default:
                $period = 'months';
                $format = 'Y-m';
                $outputFormat = 'm/Y';

                break;
        }

        $output = [];

        while ($currentDate <= $toDate) {
            $item = $data->firstWhere('date', $currentDate->format($format));

            $value = optional($item)->value;

            $output[$currentDate->format($outputFormat)] = $value ? floatval($value) : $defaultValue;
            $currentDate = $currentDate->add(1, $period);
        }

        return $output;
    }

    /** Generates data for charts
     * @param Metric $metric
     * @param Request $request
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param String|null $period
     * @return array
     * @throws Exception
     */
    public static function generateChartData(Metric $metric, Request $request, Carbon $fromDate, Carbon $toDate, String $period = null): array
    {
        $query = $metric->query($request);
        $dateField = $metric->dateField();

        if (is_subclass_of($query, Relation::class)) {
            $query = $query->getQuery();
        }

        if (is_subclass_of($query, Builder::class)) {
            $query = $query->applyScopes()->getQuery();
        }

        if (get_class($query) != \Illuminate\Database\Query\Builder::class && ! is_subclass_of($query, \Illuminate\Database\Query\Builder::class)) {
            throw new Exception('Unexpected class ' . get_class($query));
        }

        if (empty($query->columns) || isset($query->columns[1])) {
            throw new Exception('Query must return one value named \'value\'');
        }

        /* TODO: check if select is aliased value */

        if ($dateField != null) {
            self::addDateToQuery($query, $dateField, 'date', $period);
        }

        return self::formatData($query->get(), $fromDate, $toDate, $period, $metric->defaultValue());
    }
}
