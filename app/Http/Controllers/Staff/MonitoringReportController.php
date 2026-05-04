<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApplicationClearance;
use App\Models\LandTransferApplication;

class MonitoringReportController extends Controller
{
    public function index()
    {
        $statusCounts = [
            'draft' => LandTransferApplication::where('status', LandTransferApplication::STATUS_DRAFT)->count(),
            'pending_review' => LandTransferApplication::where('status', LandTransferApplication::STATUS_PENDING_REVIEW)->count(),
            'approved' => LandTransferApplication::where('status', LandTransferApplication::STATUS_APPROVED)->count(),
            'not_approved' => LandTransferApplication::where('status', LandTransferApplication::STATUS_NOT_APPROVED)->count(),
        ];

        $totalApplications = LandTransferApplication::count();
        $totalClearances = ApplicationClearance::count();
        $totalClearanceArea = ApplicationClearance::sum('total_area_hectares');

        $recentApplications = LandTransferApplication::with([
                'transferorLandowner',
                'transfereeLandowner',
                'clearance',
            ])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentClearances = ApplicationClearance::with('application')
            ->orderByDesc('generated_at')
            ->limit(10)
            ->get();

        $municipalityBreakdown = LandTransferApplication::query()
            ->select('municipality', 'status')
            ->get()
            ->groupBy(function ($application) {
                return $application->municipality ?: 'Unspecified';
            })
            ->map(function ($applications, $municipality) {
                return [
                    'municipality' => $municipality,
                    'total' => $applications->count(),
                    'draft' => $applications->where('status', LandTransferApplication::STATUS_DRAFT)->count(),
                    'pending_review' => $applications->where('status', LandTransferApplication::STATUS_PENDING_REVIEW)->count(),
                    'approved' => $applications->where('status', LandTransferApplication::STATUS_APPROVED)->count(),
                    'not_approved' => $applications->where('status', LandTransferApplication::STATUS_NOT_APPROVED)->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('staff.reports.monitoring', compact(
            'statusCounts',
            'totalApplications',
            'totalClearances',
            'totalClearanceArea',
            'recentApplications',
            'recentClearances',
            'municipalityBreakdown'
        ));
    }
}