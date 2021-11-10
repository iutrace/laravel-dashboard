<?php

declare(strict_types=1);

namespace Iutrace\Dashboard\Http;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Iutrace\Dashboard\Dashboard;
use Iutrace\Dashboard\Metric;

class DashboardController extends Controller
{
    /**
     * Returns the metric data.
     *
     * @param Request $request
     * @param Dashboard $dashboard
     * @return array
     * @throws Exception
     */
    public function data(Request $request, Dashboard $dashboard): array
    {
        $data = $request->validate([
            'metric' => [
                'required',
                Rule::in($dashboard->getMetrics()),
            ],
            'period' => '',
            'from' => '',
            'to' => '',
        ]);
        $period = $data['period'] ?? 'monthly';

        $toDate = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today()->copy()->endOfDay();
        $fromDate = isset($data['from']) ? Carbon::parse($data['from']) : Dashboard::calculateFromDate($toDate, $period);

        /** @var Metric $metric */
        $metric = new $data['metric']();

        $data = Dashboard::generateChartData($metric, $request, $fromDate, $toDate, $period);

        $output = $metric->toArray();
        $output['data'] = $data;

        if (config('app.debug') === true) {
            $query = $metric->query($request);
            Dashboard::addDateToQuery($query, $metric->dateField(), 'date', $period);
            $output['sql'] = $query->toSql();
        }

        return $output;
    }
}
