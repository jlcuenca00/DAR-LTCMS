<x-staff-shell
    title="User / Role Management"
    subtitle="Manage staff, landowner, and geodetic user accounts with role-based access and active/inactive account control."
    active="users"
>
    <x-slot name="actions">
        <a href="{{ route('staff.users.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-user-plus"></i>
            Create User
        </a>
    </x-slot>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
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

    <section class="staff-panel staff-panel-pad">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="staff-panel-title">Search and Filter Users</h2>
                <p class="staff-panel-subtitle">Filter user accounts by role, active status, name, or email address.</p>
            </div>
            <p class="text-sm font-bold text-gray-500">{{ $users->total() }} account(s)</p>
        </div>

        <form method="GET" action="{{ route('staff.users.index') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or email" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Role</label>
                <select name="role" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All roles</option>
                    @foreach (\App\Models\User::ROLES as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucwords($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="staff-button staff-button-dark"><i class="fa-solid fa-filter"></i>Apply</button>
                <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="staff-panel overflow-hidden">
        <div class="staff-panel-pad">
            <h2 class="staff-panel-title">User Accounts</h2>
            <p class="staff-panel-subtitle">Showing {{ $users->count() }} of {{ $users->total() }} user account(s).</p>
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
                        <tr>
                            <td>
                                <div class="font-bold text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </td>
                            <td><span class="staff-badge staff-badge-blue">{{ ucwords($user->role) }}</span></td>
                            <td><span class="staff-badge {{ $user->is_active ? 'staff-badge-green' : 'staff-badge-red' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                @if ($user->landowner)
                                    <div class="font-semibold text-gray-900">{{ $user->landowner->full_name }}</div>
                                    <div class="text-xs text-gray-500">Landowner ID: {{ $user->landowner->id }}</div>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $user->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</td>
                            <td class="text-right"><a href="{{ route('staff.users.edit', $user) }}" class="staff-button staff-button-light">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">No user accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4">{{ $users->withQueryString()->links() }}</div>
    </section>
</x-staff-shell>
