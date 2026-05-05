<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Monitoring and Reports
        </h2>
        <a href="{{ route('staff.reports.monitoring.print') }}"
   target="_blank"
   style="display:inline-block; background:#111827; color:#ffffff; padding:10px 14px; border-radius:6px; font-size:14px; font-weight:600; text-decoration:none; border:1px solid #111827;">
    Print / Save as PDF
</a>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="text-lg font-semibold text-gray-900">
                    Land Transfer Clearance Monitoring Summary
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    This report provides read-only monitoring information for DAR staff.
                    It summarizes application status, generated clearances, and municipal distribution.
                    It does not perform ownership transfer or registry mutation.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-5 border">
                    <div class="text-sm text-gray-500">Total Applications</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ $totalApplications }}
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 border">
                    <div class="text-sm text-gray-500">Pending Review</div>
                    <div class="text-3xl font-bold text-orange-600 mt-2">
                        {{ $statusCounts->get('pending_review', 0) }}
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 border">
                    <div class="text-sm text-gray-500">Generated Clearances</div>
                    <div class="text-3xl font-bold text-green-700 mt-2">
                        {{ $totalClearances }}
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-5 border">
                    <div class="text-sm text-gray-500">Clearance Area</div>
                    <div class="text-3xl font-bold text-gray-900 mt-2">
                        {{ number_format((float) $totalClearanceArea, 4) }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">hectares</div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Application Status Breakdown
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div class="border rounded p-4">
                        <div class="text-gray-500">Draft</div>
                        <div class="text-2xl font-bold mt-1">
                            {{ $statusCounts->get('draft', 0) }}
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <div class="text-gray-500">Pending Review</div>
                        <div class="text-2xl font-bold mt-1 text-orange-600">
                            {{ $statusCounts->get('pending_review', 0) }}
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <div class="text-gray-500">Approved</div>
                        <div class="text-2xl font-bold mt-1 text-green-700">
                            {{ $statusCounts->get('approved', 0) }}
                        </div>
                    </div>

                    <div class="border rounded p-4">
                        <div class="text-gray-500">Not Approved</div>
                        <div class="text-2xl font-bold mt-1 text-red-700">
                            {{ $statusCounts->get('not_approved', 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Municipality Breakdown
                </h3>

                @if($municipalityBreakdown->isEmpty())
                    <p class="text-sm text-gray-500">No municipality data available.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Municipality</th>
                                    <th class="border px-3 py-2 text-left">Total</th>
                                    <th class="border px-3 py-2 text-left">Draft</th>
                                    <th class="border px-3 py-2 text-left">Pending</th>
                                    <th class="border px-3 py-2 text-left">Approved</th>
                                    <th class="border px-3 py-2 text-left">Not Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($municipalityBreakdown as $row)
                                    <tr>
                                        <td class="border px-3 py-2">
                                            {{ $row['municipality'] }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $row['total'] }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $row['draft'] }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $row['pending_review'] }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $row['approved'] }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $row['not_approved'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Recent Applications
                </h3>

                @if($recentApplications->isEmpty())
                    <p class="text-sm text-gray-500">No recent applications found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Application Code</th>
                                    <th class="border px-3 py-2 text-left">Transferor</th>
                                    <th class="border px-3 py-2 text-left">Transferee</th>
                                    <th class="border px-3 py-2 text-left">Municipality</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Clearance</th>
                                    <th class="border px-3 py-2 text-left">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentApplications as $application)
                                    <tr>
                                        <td class="border px-3 py-2 font-mono">
                                            {{ $application->application_code }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->transferorLandowner?->full_name ?? $application->transferor_name ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->transfereeLandowner?->full_name ?? $application->transferee_name ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->municipality ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ strtoupper($application->status) }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            @if($application->clearance)
                                                {{ $application->clearance->clearance_number }}
                                            @else
                                                <span class="text-gray-500">None</span>
                                            @endif
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->created_at?->format('M d, Y') ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Recent Generated Clearances
                </h3>

                @if($recentClearances->isEmpty())
                    <p class="text-sm text-gray-500">No generated clearances found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Clearance No.</th>
                                    <th class="border px-3 py-2 text-left">Application Code</th>
                                    <th class="border px-3 py-2 text-left">Decision</th>
                                    <th class="border px-3 py-2 text-left">Transferor</th>
                                    <th class="border px-3 py-2 text-left">Transferee</th>
                                    <th class="border px-3 py-2 text-left">Area</th>
                                    <th class="border px-3 py-2 text-left">Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentClearances as $clearance)
                                    <tr>
                                        <td class="border px-3 py-2 font-mono">
                                            {{ $clearance->clearance_number }}
                                        </td>
                                        <td class="border px-3 py-2 font-mono">
                                            {{ $clearance->application_code }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ strtoupper($clearance->decision_status) }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $clearance->transferor_name }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $clearance->transferee_name }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ number_format((float) $clearance->total_area_hectares, 4) }} ha
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $clearance->generated_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>