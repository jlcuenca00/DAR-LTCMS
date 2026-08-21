<x-staff-shell title="Monitoring and Reports" active="reports" maxWidth="max-w-7xl">
    @php
        $normalizedStatusCounts = collect($statusCounts ?? []);
        $normalizedClearanceCounts = collect($clearanceCounts ?? []);

        $statusRows = [
            'pending_legal_review' => [
                'label' => 'Pending Review by Legal Officer',
                'count' => (int) (($normalizedStatusCounts['pending_legal_review'] ?? 0) + ($normalizedStatusCounts['pending_review'] ?? 0) + ($normalizedStatusCounts['draft'] ?? 0)),
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'endorsed_lti' => [
                'label' => 'Endorsed to LTI Division',
                'count' => (int) ($normalizedStatusCounts['endorsed_lti'] ?? 0),
                'class' => 'bg-blue-50 text-blue-800 border-blue-200',
            ],
            'endorsed_chief_legal' => [
                'label' => 'Endorsed to Chief Legal',
                'count' => (int) ($normalizedStatusCounts['endorsed_chief_legal'] ?? 0),
                'class' => 'bg-blue-50 text-blue-800 border-blue-200',
            ],
            'endorsed_parpo' => [
                'label' => 'Endorsed to PARPO II',
                'count' => (int) ($normalizedStatusCounts['endorsed_parpo'] ?? 0),
                'class' => 'bg-blue-50 text-blue-800 border-blue-200',
            ],
            'for_releasing' => [
                'label' => 'For Releasing',
                'count' => (int) ($normalizedStatusCounts['for_releasing'] ?? 0),
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
            ],
            'released' => [
                'label' => 'Released',
                'count' => (int) (($normalizedStatusCounts['released'] ?? 0) + ($normalizedStatusCounts['approved'] ?? 0)),
                'class' => 'bg-green-50 text-green-800 border-green-200',
            ],
            'denied' => [
                'label' => 'Denied',
                'count' => (int) (($normalizedStatusCounts['denied'] ?? 0) + ($normalizedStatusCounts['not_approved'] ?? 0)),
                'class' => 'bg-red-50 text-red-800 border-red-200',
            ],
        ];

        $activeApplicationCount = collect($statusRows)
            ->only(['pending_legal_review', 'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo', 'for_releasing'])
            ->sum('count');

        $releasedResults = (int) (($normalizedClearanceCounts['released'] ?? 0) + ($normalizedClearanceCounts['approved'] ?? 0));
        $deniedResults = (int) (($normalizedClearanceCounts['denied'] ?? 0) + ($normalizedClearanceCounts['not_approved'] ?? 0));
        $printParams = array_filter($filters ?? [], fn ($value) => filled($value));

        $statusClassFor = function (?string $status): string {
            return match ($status) {
                'released', 'approved' => 'bg-green-50 text-green-800 border-green-200',
                'denied', 'not_approved' => 'bg-red-50 text-red-800 border-red-200',
                'pending_legal_review', 'pending_review', 'draft', 'for_releasing' => 'bg-amber-50 text-amber-800 border-amber-200',
                default => 'bg-blue-50 text-blue-800 border-blue-200',
            };
        };

        $decisionLabel = function (?string $status): string {
            return match ($status) {
                'released', 'approved' => 'Released',
                'denied', 'not_approved' => 'Denied',
                default => ucwords(str_replace('_', ' ', (string) $status)),
            };
        };
    @endphp

    <div class="space-y-5">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-green-700">Administrative monitoring</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950">Monitoring and Reports</h1>
                <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-500">
                    Review clearance-processing activity using one consistent filtered dataset. Filters apply to summary totals, workflow counts, municipality counts, recent applications, and related final clearance outputs.
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row lg:flex-col lg:items-end">
                <div class="text-xs font-semibold text-slate-500 lg:text-right">
                    <strong class="block text-slate-700">Generated {{ $generatedAt?->timezone('Asia/Manila')->format('M d, Y h:i A') }} PHT</strong>
                    {{ $generatedBy?->name ?? 'Authorized Staff' }}
                </div>
                <a href="{{ route('staff.reports.monitoring.print', $printParams) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-green-700 px-4 text-sm font-black text-white hover:bg-green-800">
                    <i class="fa-solid fa-print"></i>
                    Print / Save PDF
                </a>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="report-filters-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="report-filters-heading" class="text-base font-black text-slate-950">Report Filters</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Date filters use Date of Application; records without that value fall back to their encoded date.</p>
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route('staff.reports.monitoring.index') }}" class="text-sm font-black text-green-700 hover:text-green-900">Clear filters</a>
                @endif
            </div>

            <form method="GET" action="{{ route('staff.reports.monitoring.index') }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Date From</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-green-700 focus:ring-green-700">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Date To</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-green-700 focus:ring-green-700">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Workflow Status</span>
                    <select name="status" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-green-700 focus:ring-green-700">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Municipality / City</span>
                    <select name="municipality" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800 focus:border-green-700 focus:ring-green-700">
                        <option value="">All municipalities / cities</option>
                        @foreach ($municipalities as $municipality)
                            <option value="{{ $municipality }}" @selected(($filters['municipality'] ?? null) === $municipality)>{{ $municipality }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="flex items-end">
                    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-green-700 px-4 text-sm font-black text-white hover:bg-green-800">
                        <i class="fa-solid fa-filter"></i>
                        Apply Filters
                    </button>
                </div>
            </form>

            @if ($hasActiveFilters)
                <div class="mt-4 flex flex-wrap gap-2" aria-label="Active report filters">
                    @foreach ($filterLabels as $label)
                        <span class="inline-flex rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-black text-green-800">{{ $label }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Report summary">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Total Applications</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($totalApplications) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">Applications matching the current report filters.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Active Applications</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($activeApplicationCount) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">Still within the administrative clearance workflow.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Recorded Results</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format($totalClearances) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">Immutable released or denied clearance-output snapshots.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Recorded Output Area</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ number_format((float) $totalClearanceArea, 4) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">hectares represented in final output snapshots; not ownership transferred.</p>
            </article>
        </section>

        <div class="grid gap-5 xl:grid-cols-[1.45fr_0.75fr]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-black text-slate-950">Workflow Status Breakdown</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Canonical workflow groups include compatible legacy statuses where applicable.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($statusRows as $row)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <span class="text-sm font-bold text-slate-700">{{ $row['label'] }}</span>
                            <span class="inline-flex min-w-10 justify-center rounded-full border px-2.5 py-1 text-xs font-black {{ $row['class'] }}">{{ number_format($row['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="space-y-5">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-black text-slate-950">Final Output Results</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Administrative clearance results only.</p>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-green-700">Released</p>
                            <p class="mt-2 text-2xl font-black text-green-950">{{ number_format($releasedResults) }}</p>
                            <p class="mt-1 text-xs font-semibold text-green-800">{{ number_format((float) $releasedOutputArea, 4) }} ha in snapshots</p>
                        </div>
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-red-700">Denied</p>
                            <p class="mt-2 text-2xl font-black text-red-950">{{ number_format($deniedResults) }}</p>
                            <p class="mt-1 text-xs font-semibold text-red-800">{{ number_format((float) $deniedOutputArea, 4) }} ha in snapshots</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-black text-slate-950">Municipality Breakdown</h2>
                    </div>
                    <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                        @forelse ($municipalityBreakdown as $row)
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <span class="truncate text-sm font-bold text-slate-700">{{ $row->municipality }}</span>
                                <span class="text-sm font-black text-green-700">{{ number_format((int) $row->total) }}</span>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-center text-sm font-semibold text-slate-500">No municipality data matches the current filters.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-black text-slate-950">Recent Applications</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Latest 10 encoded applications within the filtered dataset.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Application</th>
                            <th class="px-4 py-3">Parties</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($recentApplications as $application)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-black text-green-700">
                                    <a href="{{ route('staff.applications.show', $application) }}" class="hover:underline">{{ $application->application_code }}</a>
                                </td>
                                <td class="min-w-64 px-4 py-3 text-slate-700">
                                    <strong>{{ $application->transferorDisplayName() ?: 'Not specified' }}</strong>
                                    <div class="mt-1 text-xs font-semibold text-slate-500">to {{ $application->transfereeDisplayName() ?: 'Not specified' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600">{{ collect([$application->barangay, $application->municipality])->filter()->implode(', ') ?: 'Not specified' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600">{{ ($application->date_of_application ?? $application->created_at)?->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black {{ $statusClassFor($application->status) }}">{{ $application->statusLabel() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center font-semibold text-slate-500">No applications match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-black text-slate-950">Recent Release / Denial Outputs</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">Latest 10 immutable clearance-result snapshots tied to applications in the filtered dataset.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Clearance No.</th>
                            <th class="px-4 py-3">Application</th>
                            <th class="px-4 py-3">Decision</th>
                            <th class="px-4 py-3">Snapshot Area</th>
                            <th class="px-4 py-3">Generated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($recentClearances as $clearance)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-black text-slate-800">{{ $clearance->clearance_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-bold text-green-700">{{ $clearance->application_code }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-black {{ $statusClassFor($clearance->decision_status) }}">{{ $decisionLabel($clearance->decision_status) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600">{{ number_format((float) $clearance->total_area_hectares, 4) }} ha</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-600">{{ $clearance->generated_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center font-semibold text-slate-500">No final clearance outputs match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold leading-6 text-green-950">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-circle-info mt-1 text-green-700"></i>
                <div>
                    <strong class="block font-black">Scope Notice</strong>
                    <p class="mt-1">{{ $scopeNotice }}</p>
                    <p class="mt-2 text-xs text-green-800">{{ $areaNotice }}</p>
                </div>
            </div>
        </div>
    </div>
</x-staff-shell>
