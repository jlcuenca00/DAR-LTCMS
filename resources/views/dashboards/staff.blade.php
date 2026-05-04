<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Staff Dashboard
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="text-lg font-semibold text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h3>

                <p class="text-sm text-gray-600 mt-1">
                    You are logged in as DAR staff. You can process applications, review documents,
                    generate clearances, and monitor application records.
                </p>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('staff.reports.monitoring.index') }}"
                       class="block border rounded-lg p-5 hover:bg-gray-50 transition">
                        <h4 class="font-semibold text-gray-900">
                            Monitoring and Reports
                        </h4>
                        <p class="text-sm text-gray-600 mt-1">
                            View application status counts, clearance totals, recent records,
                            and municipality-level monitoring summaries.
                        </p>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>