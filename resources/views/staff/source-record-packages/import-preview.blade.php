<x-staff-shell
    title="Source Package Import Preview"
    subtitle="Staff-side administrative screen for DAR-LTCMS processing, records management, monitoring, and auditability."
    active="source-records"
>
<div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded border border-red-200">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border shadow-sm rounded-lg p-5">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Import Preview
                        </h3>

                        <p class="text-sm text-gray-600 mt-1">
                            File: <span class="font-semibold">{{ $batch->original_filename }}</span>
                        </p>

                        <p class="text-sm text-gray-600 mt-1">
                            Review the rows below before committing them permanently.
                        </p>
                    </div>

                    <a href="{{ route('staff.source-record-package-imports.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
                        Upload Another File
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white border shadow-sm rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500 font-semibold">Total Rows</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ $batch->total_rows }}</div>
                </div>

                <div class="bg-white border shadow-sm rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500 font-semibold">Valid Rows</div>
                    <div class="text-2xl font-bold text-green-700 mt-1">{{ $batch->valid_rows }}</div>
                </div>

                <div class="bg-white border shadow-sm rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500 font-semibold">Rows With Errors</div>
                    <div class="text-2xl font-bold text-red-700 mt-1">{{ $batch->error_rows }}</div>
                </div>

                <div class="bg-white border shadow-sm rounded-lg p-4">
                    <div class="text-xs uppercase text-gray-500 font-semibold">Possible Duplicates</div>
                    <div class="text-2xl font-bold text-yellow-700 mt-1">{{ $batch->duplicate_rows }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('staff.source-record-package-imports.commit', $batch) }}">
                @csrf

                <div class="bg-white border shadow-sm rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">Commit</th>
                                    <th class="px-4 py-3 text-left">Row</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Included</th>
                                    <th class="px-4 py-3 text-left">References</th>
                                    <th class="px-4 py-3 text-left">Owner / Location</th>
                                    <th class="px-4 py-3 text-left">Issues</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $row)
                                    @php
                                        $data = $row['data'];
                                        $isValid = $row['status'] === 'valid';
                                    @endphp

                                    <tr class="{{ $isValid ? 'hover:bg-gray-50' : 'bg-red-50' }}">
                                        <td class="px-4 py-3 align-top">
                                            @if ($isValid && $batch->status !== 'committed')
                                                <input type="checkbox"
                                                       name="selected_rows[]"
                                                       value="{{ $row['row_index'] }}"
                                                       checked
                                                       class="rounded border-gray-300 text-green-700 focus:ring-green-600">
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top font-semibold text-gray-900">
                                            {{ $row['row_index'] }}
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            @if ($row['status'] === 'valid')
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    Valid
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                    Error
                                                </span>
                                            @endif

                                            @if ($row['possible_duplicate'])
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                        Possible Duplicate
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            @if ($data['include_title'])
                                                <div>Title</div>
                                            @endif

                                            @if ($data['include_landholding'])
                                                <div>Landholding</div>
                                            @endif

                                            @if ($data['include_parcel_source'])
                                                <div>Parcel Source</div>
                                            @endif

                                            @if ($data['include_historical_clearance'])
                                                <div>Historical Clearance</div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            @if ($data['parcel_code'])
                                                <div><strong>Parcel Ref:</strong> {{ $data['parcel_code'] }}</div>
                                            @endif

                                            @if ($data['title_number'])
                                                <div><strong>Title:</strong> {{ $data['title_number'] }}</div>
                                            @endif

                                            @if ($data['landholding_reference_number'])
                                                <div><strong>Landholding:</strong> {{ $data['landholding_reference_number'] }}</div>
                                            @endif

                                            @if ($data['control_number'])
                                                <div><strong>Control:</strong> {{ $data['control_number'] }}</div>
                                            @endif

                                            @if (! $data['parcel_code'] && ! $data['title_number'] && ! $data['landholding_reference_number'] && ! $data['control_number'])
                                                —
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            <div><strong>Owner:</strong> {{ $data['landowner_name'] ?: '—' }}</div>
                                            <div><strong>Barangay:</strong> {{ $data['barangay'] ?: '—' }}</div>
                                            <div><strong>Municipality:</strong> {{ $data['municipality'] ?: '—' }}</div>
                                            <div><strong>Area:</strong> {{ $data['area_hectares'] ?: '—' }}</div>
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            @if (count($row['errors']) > 0)
                                                <div class="text-red-700 font-semibold mb-1">Errors:</div>
                                                <ul class="list-disc pl-5 space-y-1 text-red-700">
                                                    @foreach ($row['errors'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif

                                            @if (count($row['warnings']) > 0)
                                                <div class="text-yellow-700 font-semibold mt-2 mb-1">Warnings:</div>
                                                <ul class="list-disc pl-5 space-y-1 text-yellow-700">
                                                    @foreach ($row['warnings'] as $warning)
                                                        <li>{{ $warning }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif

                                            @if (count($row['errors']) === 0 && count($row['warnings']) === 0)
                                                <span class="text-gray-400">No issues</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            No rows found in this import batch.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t bg-gray-50 flex flex-wrap gap-2">
                        @if ($batch->status !== 'committed')
                            <button type="submit"
                                    class="px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                                Commit Selected Valid Rows
                            </button>
                        @else
                            <span class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm font-semibold">
                                Already Committed
                            </span>
                        @endif

                        <a href="{{ route('staff.legacy-records.index') }}"
                           class="px-4 py-2 bg-white border text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-100">
                            Back to Source Records
                        </a>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-staff-shell>
