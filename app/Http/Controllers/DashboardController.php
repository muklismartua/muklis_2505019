<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardFilterRequest $request, DashboardService $dashboardService): View
    {
        $days = (int) ($request->validated('hari') ?? 7);

        return view('dashboard', $dashboardService->data($request->user(), $days));
    }
}
