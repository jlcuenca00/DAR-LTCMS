<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            User / Role Management
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 text-red-800 p-3 rounded border border-red-200">
                    <div class="font-semibold mb-2">Action failed:</div>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Staff-Managed User Accounts
                        </h3>

                        <p class="text-sm text-gray-600 mt-1">
                            Manage authorized user accounts for DAR staff, landowners, and geodetic personnel.
                            This supports role-based access control, landowner privacy, and auditability.
                        </p>

                        <p class="text-xs text-gray-500 mt-2">
                            Note: This page only controls system access. It does not process land ownership transfer or registry mutation.
                        </p>
                    </div>

                    <a href="{{ route('staff.users.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm font-semibold hover:bg-gray-800">
                        Create User
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Filter Users
                </h3>

                <form method="GET"
                      action="{{ route('staff.users.index') }}"
                      class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Role
                        </label>

                        <select name="role"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All roles</option>

                            <option value="staff" {{ ($filters['role'] ?? '') === 'staff' ? 'selected' : '' }}>
                                Staff
                            </option>

                            <option value="landowner" {{ ($filters['role'] ?? '') === 'landowner' ? 'selected' : '' }}>
                                Landowner
                            </option>

                            <option value="geodetic" {{ ($filters['role'] ?? '') === 'geodetic' ? 'selected' : '' }}>
                                Geodetic
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status
                        </label>

                        <select name="status"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All statuses</option>

                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Name or email"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Apply Filters
                        </button>

                        <a href="{{ route('staff.users.index') }}"
                           class="px-4 py-2 border rounded-md text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        User Accounts
                    </h3>

                    <div class="text-sm text-gray-500">
                        Showing {{ $users->count() }} of {{ $users->total() }} account(s)
                    </div>
                </div>

                @if ($users->isEmpty())
                    <p class="text-sm text-gray-500">
                        No user accounts found.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-3 py-2 text-left">Name</th>
                                    <th class="border px-3 py-2 text-left">Email</th>
                                    <th class="border px-3 py-2 text-left">Role</th>
                                    <th class="border px-3 py-2 text-left">Status</th>
                                    <th class="border px-3 py-2 text-left">Linked Landowner</th>
                                    <th class="border px-3 py-2 text-left">Created</th>
                                    <th class="border px-3 py-2 text-left">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($users as $user)
                                    <tr class="align-top">
                                        <td class="border px-3 py-2">
                                            <div class="font-medium text-gray-900">
                                                {{ $user->name }}
                                            </div>

                                            @if (auth()->id() === $user->id)
                                                <div class="text-xs text-blue-700">
                                                    Current account
                                                </div>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2">
                                            {{ $user->email }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            <span class="inline-flex px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs font-semibold">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($user->is_active)
                                                <span class="inline-flex px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 rounded bg-red-100 text-red-800 text-xs font-semibold">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2">
                                            @if ($user->landowner)
                                                <div class="font-medium text-gray-900">
                                                    {{ $user->landowner->full_name }}
                                                </div>

                                                <div class="text-xs text-gray-500">
                                                    Landowner ID: {{ $user->landowner->id }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">Not linked</span>
                                            @endif
                                        </td>

                                        <td class="border px-3 py-2 whitespace-nowrap">
                                            {{ $user->created_at?->format('M d, Y') ?? 'N/A' }}
                                        </td>

                                        <td class="border px-3 py-2">
                                            <a href="{{ route('staff.users.edit', $user) }}"
                                               class="text-blue-700 hover:underline font-semibold">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>