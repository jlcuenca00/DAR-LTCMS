<x-staff-shell
    title="User / Role Management"
    active="users"
>
    <x-slot name="actions">
        <a href="{{ route('staff.users.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-user-plus"></i>
            Create User
        </a>
    </x-slot>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-800">
            <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <section class="staff-scope-banner">
        <div>
            <h3>Staff-Managed User Accounts</h3>
            <p>
                User management controls system access only. Landowner accounts must be linked to one landowner record, geodetic access remains limited/read-only, and staff actions remain audit logged.
            </p>
        </div>
        <span class="staff-scope-pill">RBAC Protected</span>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="staff-panel-title">Search and Filter Users</h2>
                    <p class="staff-panel-subtitle">Filter user accounts by name, email, role, or active status.</p>
                </div>
                <span class="staff-badge staff-badge-green self-start xl:self-auto">{{ $users->total() }} account(s)</span>
            </div>

            <form method="GET" action="{{ route('staff.users.index') }}" class="mt-5 grid gap-3 lg:grid-cols-[minmax(240px,1fr)_180px_180px_auto] lg:items-end">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or email" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Role</label>
                    <select name="role" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        <option value="">All roles</option>
                        @foreach (\App\Models\User::ROLES as $role)
                            <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucwords($role) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500">Status</label>
                    <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="staff-button staff-button-dark whitespace-nowrap">
                        <i class="fa-solid fa-filter"></i>
                        Apply
                    </button>
                    <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Linked Landowner</th>
                        <th>Created</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $roleBadgeClass = match ($user->role) {
                                'staff' => 'border-green-200 bg-green-50 text-green-800',
                                'geodetic' => 'border-slate-200 bg-slate-50 text-slate-700',
                                'landowner' => 'border-slate-200 bg-slate-50 text-slate-700',
                                default => 'border-slate-200 bg-slate-50 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-800 text-xs font-bold text-white">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-950">{{ $user->name }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $roleBadgeClass }}">
                                    {{ ucwords($user->role) }}
                                </span>
                            </td>
                            <td>
                                <span class="staff-badge {{ $user->is_active ? 'staff-badge-green' : 'staff-badge-red' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if ($user->landowner)
                                    <div class="font-semibold text-gray-900">{{ $user->landowner->full_name }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">Landowner ID: {{ $user->landowner->id }}</div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $user->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-right">
                                <a href="{{ route('staff.users.edit', $user) }}" class="staff-button staff-button-light">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-gray-500">
                                No user accounts found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </section>
</x-staff-shell>
