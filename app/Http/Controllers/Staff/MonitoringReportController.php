<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;
use Illuminate\Support\Facades\Auth;

class MonitoringReportController extends Controller
{
    public function index()
    {
        return view('staff.reports.monitoring', $this->buildReportData());
    }

    public function print()
    {
        return view('staff.reports.monitoring-print', $this->buildReportData());
    }

    private function buildReportData(): array
    {
        $statusCounts = LandTransferApplication::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status');

        $clearanceCounts = ApplicationClearance::query()
            ->selectRaw('decision_status, COUNT(*) as total')
            ->groupBy('decision_status')
            ->orderBy('decision_status')
            ->pluck('total', 'decision_status');

        $totalApplications = LandTransferApplication::count();

        $totalClearances = ApplicationClearance::count();

        $totalClearanceArea = ApplicationClearance::query()
            ->sum('total_area_hectares');

        $municipalityBreakdown = LandTransferApplication::query()
            ->selectRaw('municipality, COUNT(*) as total')
            ->whereNotNull('municipality')
            ->groupBy('municipality')
            ->orderBy('municipality')
            ->get();

        $recentApplications = LandTransferApplication::query()
            ->latest()
            ->limit(10)
            ->get();

        $recentClearances = ApplicationClearance::query()
            ->latest('generated_at')
            ->limit(10)
            ->get();

        return [
            'statusCounts' => $statusCounts,
            'clearanceCounts' => $clearanceCounts,
            'totalApplications' => $totalApplications,
            'totalClearances' => $totalClearances,
            'totalClearanceArea' => $totalClearanceArea,
            'municipalityBreakdown' => $municipalityBreakdown,
            'recentApplications' => $recentApplications,
            'recentClearances' => $recentClearances,
            'generatedAt' => now(),
            'generatedBy' => Auth::user(),
            'scopeNotice' => 'This report is generated for administrative monitoring and decision-support purposes only. Clearance approval or report inclusion does not automatically transfer land ownership, mutate parcel ownership records, or replace separate legal and administrative procedures.',
        ];
    }
}