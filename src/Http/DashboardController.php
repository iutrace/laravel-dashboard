<?php
declare(strict_types=1);

namespace Iutrace\Dashboard\Http;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Iutrace\Dashboard\Dashboard;
use Iutrace\Dashboard\Models\Metric;

class DashboardController extends Controller
{
    /**
     * Returns the metric data.
     *
     * @param Illuminate\Http\Request $request
     * @return Iutrace\Dashboard\Models\Metric
     */
    public function data(Request $request, Dashboard $dashboard)
    {
        $request->validate([
            'metric' => [
                'required',
                Rule::in($dashboard->getMetrics())
            ],
        ]);

        $period = $request->period ?? 'monthly';
        $toDate = today()->copy()->endOfDay();
        $fromDate = Dashboard::calculateFromDate($toDate, $period);

        /** @var Metric $metric */
        $metric = new $request->metric($request, $fromDate, $toDate, $period);
        $data = $metric->query();

        $labelMap = [
            'value' => $metric::name(),
        ];

        return Dashboard::generateChartData($data, $labelMap, $fromDate, $toDate, null, $period);
    }
}
