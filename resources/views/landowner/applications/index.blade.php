<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Application Status
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    These are clearance applications where your landowner record is listed as transferor or transferee.
                </p>

                @if($applications->isEmpty())
                    <p class="text-gray-500">No applications found for your account.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Application Code</th>
                                    <th class="border px-3 py-2 text-left">Transferor</th>
                                    <th class="border px-3 py-2 text-left">Transferee</th>
                                    <th class="border px-3 py-2 text-left">Location</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Decision Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
                                    <tr>
                                        <td class="border px-3 py-2">
                                            {{ $application->application_code }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->transferorLandowner?->full_name ?? $application->transferor_name }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->transfereeLandowner?->full_name ?? $application->transferee_name }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->barangay ?? 'N/A' }},
                                            {{ $application->municipality ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            <span class="font-semibold">
                                                {{ str_replace('_', ' ', ucfirst($application->status)) }}
                                            </span>
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $application->reviewed_at?->format('M d, Y') ?? 'Pending' }}
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