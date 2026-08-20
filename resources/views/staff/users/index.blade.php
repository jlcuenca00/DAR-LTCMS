<x-staff-shell
    title="User Management"
    active="users"
>
    <style>
        .user-management-shell {
            display: grid;
            gap: 1rem;
        }

        .user-management-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.15rem 1.25rem;
            border: 1px solid #d9e2dc;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
        }

        .user-management-kicker {
            margin: 0 0 .2rem;
            color: #047857;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .user-management-title {
            margin: 0;
            color: #0f172a;
            font-size: 1.2rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .user-management-copy {
            margin: .25rem 0 0;
            color: #64748b;
            font-size: .83rem;
            line-height: 1.5;
        }

        .user-management-hero-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .user-management-policy {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            padding: .8rem .95rem;
            border: 1px solid #dbe4de;
            border-radius: .9rem;
            background: #f8faf9;
            color: #475569;
            font-size: .78rem;
            line-height: 1.5;
        }

        .user-management-policy i {
            margin-top: .12rem;
            color: #15803d;
        }

        .user-management-filter-card {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .user-management-filter-grid {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) 180px 180px auto;
            gap: .75rem;
            align-items: end;
        }

        .user-management-label {
            display: block;
            margin-bottom: .35rem;
            color: #64748b;
            font-size: .66rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .user-management-control {
            width: 100%;
            min-height: 44px;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: #ffffff;
            color: #0f172a;
            font-size: .87rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .user-management-control:focus {
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
            outline: none;
        }

        .user-management-filter-actions {
            display: flex;
            gap: .5rem;
        }

        .user-management-filter-actions .staff-button {
            min-height: 44px;
        }

        .user-management-table tbody tr {
            transition: background-color .14s ease;
        }

        .user-management-table tbody tr:hover {
            background: #fbfdfb;
        }

        .user-management-avatar {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            overflow: hidden;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #166534;
            color: #ffffff;
            font-size: .82rem;
            font-weight: 900;
        }

        .user-management-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-management-primary {
            color: #0f172a;
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.3;
        }

        .user-management-secondary {
            margin-top: .15rem;
            color: #64748b;
            font-size: .73rem;
            line-height: 1.4;
        }

        .user-management-role {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-height: 28px;
            padding: .25rem .65rem;
            border: 1px solid #d7ded9;
            border-radius: 999px;
            background: #f8faf9;
            color: #334155;
            font-size: .72rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .user-management-role.staff {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .user-management-status-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .35rem;
        }

        .user-management-empty {
            padding: 3rem 1rem !important;
            text-align: center;
            color: #64748b;
        }

        .user-management-empty i {
            display: block;
            margin-bottom: .6rem;
            color: #94a3b8;
            font-size: 1.4rem;
        }

        @media (max-width: 1080px) {
            .user-management-filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .user-management-filter-search,
            .user-management-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 720px) {
            .user-management-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .user-management-hero-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .user-management-hero-actions .staff-button {
                flex: 1 1 auto;
                justify-content: center;
            }

            .user-management-filter-grid {
                grid-template-columns: 1fr;
            }

            .user-management-filter-search,
            .user-management-filter-actions {
                grid-column: auto;
            }

            .user-management-filter-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .user-management-filter-actions .staff-button {
                justify-content: center;
            }
        }
    </style>

    <div class="user-management-shell">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-800">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <section class="user-management-hero">
            <div>
                <p class="user-management-kicker">Account Administration</p>
                <h2 class="user-management-title">Authorized User Accounts</h2>
                <p class="user-management-copy">Create accounts, assign role-based access, and manage sign-in availability without removing historical records.</p>
            </div>

            <div class="user-management-hero-actions">
                <span class="staff-badge staff-badge-green">{{ $users->total() }} account{{ $users->total() === 1 ? '' : 's' }}</span>
                <a href="{{ route('staff.users.create') }}" class="staff-button staff-button-primary">
                    <i class="fa-solid fa-user-plus"></i>
                    Create User
                </a>
            </div>
        </section>

        <div class="user-management-policy">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <div><strong>Account records are preserved.</strong> Use Active/Inactive status to control access; historical actions and audit references remain traceable.</div>
        </div>

        <section class="staff-panel overflow-hidden">
            <div class="user-management-filter-card">
                <form method="GET" action="{{ route('staff.users.index') }}" class="user-management-filter-grid">
                    <div class="user-management-filter-search">
                        <label class="user-management-label" for="user-search">Search accounts</label>
                        <input
                            id="user-search"
                            type="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Name, username, or email"
                            class="user-management-control"
                            autocomplete="off"
                        >
                    </div>

                    <div>
                        <label class="user-management-label" for="user-role">Role</label>
                        <select id="user-role" name="role" class="user-management-control">
                            <option value="">All roles</option>
                            @foreach (\App\Models\User::ROLES as $role)
                                <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucwords($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="user-management-label" for="user-status">Status</label>
                        <select id="user-status" name="status" class="user-management-control">
                            <option value="">All statuses</option>
                            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="user-management-filter-actions">
                        <button type="submit" class="staff-button staff-button-dark">
                            <i class="fa-solid fa-filter"></i>
                            Apply
                        </button>
                        <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">Clear</a>
                    </div>
                </form>
            </div>

            <div class="staff-table-wrap">
                <table class="staff-table user-management-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Access</th>
                            <th>Account</th>
                            <th>Linked Record</th>
                            <th>Activity</th>
                            <th class="staff-table-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $profilePhotoExists = filled($user->profile_photo_path)
                                    && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo_path);
                                $roleIcon = match ($user->role) {
                                    'staff' => 'fa-user-shield',
                                    'geodetic' => 'fa-draw-polygon',
                                    'landowner' => 'fa-user',
                                    default => 'fa-user',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="user-management-avatar">
                                            @if ($profilePhotoExists)
                                                <img src="{{ route('profile.photo', $user) }}" alt="">
                                            @else
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="user-management-primary">
                                                {{ $user->name }}
                                                @if (auth()->id() === $user->id)
                                                    <span class="ml-1 text-[10px] font-black uppercase tracking-wider text-green-700">You</span>
                                                @endif
                                            </div>
                                            <div class="user-management-secondary">{{ '@' . $user->username }}</div>
                                            @if (filled($user->email))
                                                <div class="user-management-secondary">{{ $user->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-management-role {{ $user->role === 'staff' ? 'staff' : '' }}">
                                        <i class="fa-solid {{ $roleIcon }}" aria-hidden="true"></i>
                                        {{ ucwords($user->role) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="user-management-status-stack">
                                        <span class="staff-badge {{ $user->is_active ? 'staff-badge-green' : 'staff-badge-red' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if ($user->must_change_password)
                                            <span class="staff-badge staff-badge-amber">Password Change Required</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($user->landowner)
                                        <div class="user-management-primary">{{ $user->landowner->full_name }}</div>
                                        <div class="user-management-secondary">Landowner ID {{ $user->landowner->id }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">Not applicable</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($user->last_login_at)
                                        <div class="user-management-primary">{{ $user->last_login_at->timezone('Asia/Manila')->format('M d, Y') }}</div>
                                        <div class="user-management-secondary">Last login · {{ $user->last_login_at->timezone('Asia/Manila')->format('h:i A') }}</div>
                                    @else
                                        <div class="user-management-primary text-gray-500">Never signed in</div>
                                    @endif
                                    <div class="user-management-secondary">Created {{ $user->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</div>
                                </td>
                                <td class="staff-table-action">
                                    <div class="staff-table-action-group">
                                        <a href="{{ route('staff.users.edit', $user) }}" class="staff-button staff-button-light">
                                            <i class="fa-solid fa-sliders"></i>
                                            Manage
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="user-management-empty">
                                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                                    <strong>No matching accounts.</strong>
                                    <div class="mt-1 text-xs">Try clearing the current search or filters.</div>
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
    </div>
</x-staff-shell>
