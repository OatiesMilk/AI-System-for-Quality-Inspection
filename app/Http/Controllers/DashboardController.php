<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Defect;
use App\Models\Inspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Land the user on the dashboard for their role.
     */
    public function index(Request $request): View|RedirectResponse
    {
        return match ($request->user()->role) {
            'quality_inspector' => redirect()->route('dashboard.inspector'),
            'product_manager' => redirect()->route('dashboard.manager'),
            'system_admin' => redirect()->route('dashboard.admin'),
            'shoe_constructor' => redirect()->route('dashboard.constructor'),
            default => abort(403, 'No dashboard is configured for your role.'),
        };
    }

    public function inspector(): View
    {
        $pendingInspections = Inspection::with('batch')
            ->whereNull('action')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboards.inspector', compact('pendingInspections'));
    }

    public function manager(): View
    {
        $defectCounts = Defect::query()
            ->selectRaw('defect_type, count(*) as total')
            ->groupBy('defect_type')
            ->pluck('total', 'defect_type');

        $recentBatches = Batch::with('inspections')->latest()->limit(10)->get();

        return view('dashboards.manager', compact('defectCounts', 'recentBatches'));
    }

    public function admin(): View
    {
        $users = User::orderBy('role')->orderBy('name')->get();

        return view('dashboards.admin', compact('users'));
    }

    public function constructor(): View
    {
        $reworkInspections = Inspection::with('batch', 'defects')
            ->where('action', 'rework')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboards.constructor', compact('reworkInspections'));
    }
}
