<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Geodetic Application Reference
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg text-gray-900">
                        Read-Only Land Transfer Clearance Applications
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        This page is for reference and verification review only.
                        Geodetic users cannot submit, approve, reject, upload documents, or generate clearances.
                    </p>
                </div>

                @if($applications->isEmpty())
                    <p class="text-gray-500">No applications found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Application Code</th>
                                    <th class="border px-3 py-2 text-left">Transferor</th>
                                    <th class="border px-3 py-2 text-left">Transferee</th>
                                    <th class="border px-3 py-2 text-left">Location</th>
                                    <th class="border px-3 py-2 text-left">Parcels</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Clearance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applications as $application)
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
                                            {{ $application->barangay ?? 'N/A' }},
                                            {{ $application->municipality ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            @forelse($application->applicationParcels as $applicationParcel)
                                                <div>
                                                    {{ $applicationParcel->parcel?->parcel_code ?? 'Unlinked parcel' }}
                                                    —
                                                    {{ number_format((float) $applicationParcel->area_hectares, 4) }} ha
                                                </div>
                                            @empty
                                                <span class="text-gray-500">No parcels linked</span>
                                            @endforelse
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ strtoupper($application->status) }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            @if($application->clearance)
                                                {{ $application->clearance->clearance_number }}
                                            @else
                                                <span class="text-gray-500">Not generated</span>
                                            @endif
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