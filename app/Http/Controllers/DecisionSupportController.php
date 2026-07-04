<?php

namespace App\Http\Controllers;

use App\Services\DecisionSupportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DecisionSupportController extends Controller
{
    public function index(Request $request, DecisionSupportService $service): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $to   = $request->filled('date_to')   ? Carbon::parse($request->string('date_to'))   : now();
        $from = $request->filled('date_from')  ? Carbon::parse($request->string('date_from')) : $to->copy();

        $report = $service->buildReport($from, $to);

        return view('dashboards.decision-support', compact('report'));
    }
}
