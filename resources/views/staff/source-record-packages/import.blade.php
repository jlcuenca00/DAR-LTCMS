<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Import Source Packages
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                <h3 class="text-lg font-semibold text-gray-900">
                    Bulk Import Source Packages
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    Upload a completed CSV template to preview source packages before saving them permanently.
                    Valid rows may be committed after review. Error rows will be blocked until corrected.
                </p>

                <div class="mt-4">
                    <a href="{{ route('staff.source-record-package-imports.template') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-black">
                        Download CSV Template
                    </a>
                </div>
            </div>

            <form method="POST"
                  action="{{ route('staff.source-record-package-imports.preview.store') }}"
                  enctype="multipart/form-data"
                  class="bg-white border shadow-sm rounded-lg p-5 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Completed CSV File *
                    </label>

                    <input type="file"
                           name="import_file"
                           accept=".csv,.txt"
                           class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md p-2">

                    <p class="text-xs text-gray-500 mt-2">
                        Use the downloaded template. Keep the header row unchanged. GeoJSON values must remain valid JSON text.
                    </p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-sm text-yellow-900">
                    <div class="font-semibold">Import reminder</div>
                    <p class="mt-1">
                        Imported source packages are documentary/provenance records only. They do not automatically transfer
                        ownership and they do not appear on the map unless staff later creates or links a main Parcel Record.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-green-700 text-white rounded-md text-sm font-semibold hover:bg-green-800">
                        Upload and Preview
                    </button>

                    <a href="{{ route('staff.legacy-records.index') }}"
                       class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200">
                        Cancel
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>