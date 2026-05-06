<x-app-layout>
    <x-system-scope-notice
    title="Geodetic Access Scope"
    variant="blue"
/>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Geodetic Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    You are logged in as a geodetic user. This account is limited to read-only parcel,
                    landholding, and application reference review.
                </p>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="{{ route('geodetic.parcel-map.index') }}"
       class="block border rounded-lg p-5 hover:bg-gray-50 transition">
        <h4 class="font-semibold text-gray-900">
            Parcel Map Viewer
        </h4>
        <p class="text-sm text-gray-600 mt-1">
            Review mapped parcel/reference data through a read-only geodetic map viewer.
        </p>
    </a>

    <a href="{{ route('geodetic.parcels.index') }}"
       class="block border rounded-lg p-5 hover:bg-gray-50 transition">
        <h4 class="font-semibold text-gray-900">
            Parcel Reference
        </h4>
        <p class="text-sm text-gray-600 mt-1">
            View parcel and landholding records for technical reference.
        </p>
    </a>

    <a href="{{ route('geodetic.applications.index') }}"
       class="block border rounded-lg p-5 hover:bg-gray-50 transition">
        <h4 class="font-semibold text-gray-900">
            Application Reference
        </h4>
        <p class="text-sm text-gray-600 mt-1">
            Review land transfer clearance applications without decision controls.
        </p>
    </a>
</div>

                <div class="mt-6 rounded-md bg-yellow-50 border border-yellow-200 p-4">
                    <p class="text-sm text-yellow-800">
                        Read-only restriction: geodetic users cannot approve, mark not approved,
                        upload documents, edit records, or generate clearance decisions.
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>