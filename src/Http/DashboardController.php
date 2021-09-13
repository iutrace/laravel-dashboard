<?php

namespace Iutrace\Dashboard\Http;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Iutrace\Dashboard\Dashboard;

class DashboardController extends Controller
{
    public function data(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'metric' => [
                'required',
                Rule::in($dashboard->getMetrics())
            ],
        ]);
        
        $metric = $request->get('metric');

        $company = \Auth::user()->company;

        if ($request->has('period')) {
            $data = $this->$function($request->period);
        } else {
            $data = $this->$function($request->get('from'), $request->get('to'), $company, $request->has('insurance_company_id') ? $request->insurance_company_id : null);
        }

        if ($request->has('json')) {
            return $data;
        }
    }
}
