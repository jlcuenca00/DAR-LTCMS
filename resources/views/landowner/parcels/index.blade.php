<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Parcel Records
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    These are parcel and landholding records linked to your landowner account only.
                </p>

                @if($landholdings->isEmpty())
                    <p class="text-gray-500">No parcel records found for your account.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Parcel Code</th>
                                    <th class="border px-3 py-2 text-left">Title No.</th>
                                    <th class="border px-3 py-2 text-left">Tax Declaration No.</th>
                                    <th class="border px-3 py-2 text-left">Location</th>
                                    <th class="border px-3 py-2 text-left">Area</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($landholdings as $holding)
                                    <tr>
                                        <td class="border px-3 py-2">
                                            {{ $holding->parcel?->parcel_code ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $holding->parcel?->title_no ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $holding->parcel?->tax_decl_no ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ $holding->parcel?->barangay ?? 'N/A' }},
                                            {{ $holding->parcel?->municipality ?? 'N/A' }}
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ number_format((float) $holding->area_hectares, 4) }} ha
                                        </td>
                                        <td class="border px-3 py-2">
                                            {{ ucfirst($holding->status) }}
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