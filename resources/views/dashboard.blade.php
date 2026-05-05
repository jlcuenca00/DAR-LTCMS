<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold">
                        Welcome, {{ auth()->user()->name }}
                    </h3>

                    <p class="text-sm text-gray-600 mt-1">
                        You are logged in to the Department of Agrarian Reform Land Transfer Clearance and Monitoring System.
                    </p>
                </div>
            </div>

            @if (auth()->user()?->role === 'staff')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            Staff Dashboard
                        </h3>

                        <p class="text-sm text-gray-600 mb-6">
                            Access staff tools for application processing, monitoring, reporting, account management, record lookup, and audit review.
                            This system supports clearance generation and monitoring only, and does not automatically transfer land ownership.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                            @if (\Illuminate\Support\Facades\Route::has('staff.applications.index'))
                                <a href="{{ route('staff.applications.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        Land Transfer Applications
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        View, encode, review, and process clearance applications.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('staff.records.landowners.index'))
                                <a href="{{ route('staff.records.landowners.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        Landowner Records
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        Search and review landowner records used for clearance processing, account linking, and monitoring.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('staff.records.parcels.index'))
                                <a href="{{ route('staff.records.parcels.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        Parcel Records
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        Search and review parcel records by parcel code, title number, tax declaration number, location, and status.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('staff.reports.monitoring.index'))
                                <a href="{{ route('staff.reports.monitoring.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        Monitoring Reports
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        View application counts, clearance totals, municipality breakdowns, and recent records.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('staff.audit-logs.index'))
                                <a href="{{ route('staff.audit-logs.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        Audit Logs
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        View system activity history, staff actions, document changes, decisions, and clearance-related audit records.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('staff.users.index'))
                                <a href="{{ route('staff.users.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        User / Role Management
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        Manage authorized staff, landowner, and geodetic user accounts, role assignments, account status, and landowner account links.
                                    </div>
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()?->role === 'landowner')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            Landowner Portal
                        </h3>

                        <p class="text-sm text-gray-600 mb-6">
                            You may view only your own parcel records and application status records.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if (\Illuminate\Support\Facades\Route::has('landowner.parcels.index'))
                                <a href="{{ route('landowner.parcels.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        My Parcels
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        View parcel records linked to your landowner account.
                                    </div>
                                </a>
                            @endif

                            @if (\Illuminate\Support\Facades\Route::has('landowner.applications.index'))
                                <a href="{{ route('landowner.applications.index') }}"
                                   class="block p-4 bg-white border rounded-lg shadow-sm hover:bg-gray-50">
                                    <div class="font-semibold text-gray-900">
                                        My Applications
                                    </div>

                                    <div class="text-sm text-gray-600 mt-1">
                                        View your clearance application status.
                                    </div>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()?->role === 'geodetic')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            Geodetic Reference Access
                        </h3>

                        <p class="text-sm text-gray-600">
                            Your account has limited read-only access for parcel, reference, and map-related review.
                        </p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
