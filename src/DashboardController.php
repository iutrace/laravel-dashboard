<?php

namespace Iutrace\Dashboard;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function data(Request $request, DashboardServiceProvider $dashboardProvider){
        $data = $request->validate([
            'metric' => [
                'required',
                Rule::in($dashboardProvider->metrics)
            ],
        ]);
    }
}