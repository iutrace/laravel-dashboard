<?php
declare(strict_types=1);

namespace Iutrace\Dashboard;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $metrics[] = (string) Str::of(substr($file->getFilename(), 0, -4))->snake();
        }

        return $metrics;
    }

    /** Fills missing dates from the given result
     * @param Collection $data Input results data
     * @param array $fields Fields that data has
     * @param Carbon $currentDate
     * @param Carbon $toDate
     * @param String|null $period Period of date "jumps"
     * @return Collection
     */
    public static function fillDateGaps($data, array $labelMap, Carbon $fromDate, Carbon $toDate, String $period = null, String $dateField = 'date'): Collection
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
                $format = 'W';
                $outputFormat = 'd/m/Y';
                break;
            default:
                $period = 'months';
                $format = 'Y-m';
                $outputFormat = 'm/Y';
                break;
        }

        $output = collect();

        while ($currentDate <= $toDate) {
            $item = $data->firstWhere($dateField, $currentDate->format($format));

            if ($item == null) {
                $item = new \stdClass();
                foreach ($labelMap as $field => $name) {
                    $item->$field = 0.0;
                }
            }

            $item->$dateField = $currentDate->format($outputFormat);

            $output->push($item);
            $currentDate = $currentDate->add(1, $period);
        }

        return $output;
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
                $dateFormat = '\'%v\'';
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

    /** Generates data for charts
     * @param mixed $query
     * @param array $labelMap Map of key->name entries, being key the field in query and name the label at the chart
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param String|null $dateField Name of the date field at query
     * @param String|null $period
     * @return array
     */
    public static function generateChartData($query, array $labelMap, Carbon $fromDate, Carbon $toDate, String $dateField = null, String $period = null): array
    {
        if (is_subclass_of($query, Relation::class)) {
            $query = $query->getQuery();
        }

        if (is_subclass_of($query, \Illuminate\Database\Eloquent\Builder::class)) {
            $query = $query->applyScopes()->getQuery();
        }

        if (get_class($query) != \Illuminate\Database\Query\Builder::class && ! is_subclass_of($query, \Illuminate\Database\Query\Builder::class)) {
            throw new \Exception('Unexpected class ' . get_class($query));
        }

        if ($dateField != null) {
            self::addDateToQuery($query, $dateField, 'date', $period);
        }

        $rawData = self::fillDateGaps($query->get(), $labelMap, $fromDate, $toDate, $period);

        $data['labels'] = $rawData->pluck('date');
        $data['datasets'] = [];

        foreach ($labelMap as $key => $name) {
            $data['datasets'][$name] = $rawData->pluck($key);
        }

        return $data;
    }

    public static function getOwnershipCondition($query): String
    {
        $wheres = collect($query->wheres);
        $where = $wheres->where('operator', '=')->first();

        return $where['column'] . ' = ' . $where['value'];
    }
}
