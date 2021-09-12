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
    }
}
