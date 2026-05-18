<x-staff-shell
    title="Create User Account"
    active="users"
>
    <x-slot name="actions">
        <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Users
        </a>
    </x-slot>

    <style>
        .user-editor-wrap {
            max-width: 1240px;
            margin: 0 auto;
        }

        .user-editor-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.35rem 1.5rem;
            border: 1px solid #d9e2dc;
            border-radius: 1.25rem;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .user-editor-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .user-editor-avatar {
            width: 3rem;
            height: 3rem;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 1rem;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-weight: 900;
            font-size: 1rem;
        }

        .user-editor-kicker {
            margin: 0 0 .25rem;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #047857;
        }

        .user-editor-title {
            margin: 0;
            color: #020617;
            font-size: 1.45rem;
            line-height: 1.15;
            font-weight: 900;
        }

        .user-editor-muted {
            margin: .3rem 0 0;
            color: #64748b;
            font-size: .9rem;
            line-height: 1.5;
        }

        .user-editor-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
        }

        .user-editor-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 365px;
            gap: 1.25rem;
            align-items: start;
        }

        .user-editor-main,
        .user-editor-side {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            min-width: 0;
        }

        .user-editor-side {
            position: sticky;
            top: 5.5rem;
        }

        .user-card {
            border: 1px solid #d9e2dc;
            border-radius: 1.25rem;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.045);
            overflow: hidden;
        }

        .user-card-head {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            padding: 1.2rem 1.35rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .user-card-icon {
            width: 2.45rem;
            height: 2.45rem;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: .9rem;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: .9rem;
        }

        .user-card-title {
            margin: 0;
            color: #020617;
            font-size: 1.05rem;
            line-height: 1.25;
            font-weight: 900;
        }

        .user-card-copy {
            margin: .25rem 0 0;
            color: #64748b;
            font-size: .87rem;
            line-height: 1.55;
        }

        .user-card-body {
            padding: 1.35rem;
        }

        .user-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .user-field {
            min-width: 0;
        }

        .user-label {
            display: block;
            margin-bottom: .45rem;
            color: #334155;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .user-input,
        .user-select {
            width: 100%;
            min-height: 2.65rem;
            border: 1px solid #cbd5e1;
            border-radius: .8rem;
            background: #fff;
            color: #0f172a;
            font-size: .92rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
        }

        .user-input:focus,
        .user-select:focus {
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
            outline: none;
        }

        .user-note {
            border: 1px solid #e2e8f0;
            border-radius: .95rem;
            background: #f8fafc;
            padding: .85rem 1rem;
            color: #475569;
            font-size: .8rem;
            line-height: 1.55;
        }

        .user-check-card {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #f8fafc;
            padding: 1rem;
        }



        .user-check-card input {
            accent-color: #15803d !important;
        }

        .user-toggle-card input {
            position: absolute;
            inline-size: 1px;
            block-size: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
        }

        .user-toggle-card-compact {
            min-height: 3rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem .85rem;
        }

        .user-toggle-title {
            display: block;
            color: #0f172a;
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.2;
            white-space: nowrap;
        }

        .user-toggle-indicator {
            position: relative;
            width: 2.55rem;
            height: 1.45rem;
            flex: 0 0 2.55rem;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
            transition: background .16s ease, border-color .16s ease, box-shadow .16s ease;
        }

        .user-toggle-indicator::after {
            content: "";
            position: absolute;
            top: 50%;
            left: .18rem;
            width: 1.05rem;
            height: 1.05rem;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .2);
            transform: translateY(-50%);
            transition: transform .16s ease;
        }

        .user-toggle-card input:checked + .user-toggle-indicator {
            border-color: #15803d;
            background: #15803d;
        }

        .user-toggle-card input:checked + .user-toggle-indicator::after {
            transform: translate(1.1rem, -50%);
        }

        .user-toggle-card input:focus-visible + .user-toggle-indicator {
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .16);
        }


        .user-normal-check-card {
            display: flex;
            align-items: center;
            gap: .75rem;
            width: 100%;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #f8fafc;
            padding: .85rem .95rem;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
        }

        .user-normal-check-card:hover {
            border-color: #bbf7d0;
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .035);
        }

        .user-native-checkbox {
            appearance: none;
            -webkit-appearance: none;
            display: inline-grid;
            place-content: center;
            width: 1.05rem;
            height: 1.05rem;
            margin: 0;
            flex: 0 0 auto;
            cursor: pointer;
            border: 1.5px solid #86efac;
            border-radius: .3rem;
            background: #ffffff;
            transition: background-color .14s ease, border-color .14s ease, box-shadow .14s ease;
        }

        .user-native-checkbox::before {
            content: '';
            width: .58rem;
            height: .58rem;
            transform: scale(0);
            transition: transform .12s ease-in-out;
            clip-path: polygon(14% 44%, 0 58%, 38% 96%, 100% 22%, 86% 8%, 36% 68%);
            background: #ffffff;
        }

        .user-native-checkbox:checked {
            background: #15803d !important;
            border-color: #15803d !important;
            accent-color: #15803d !important;
        }

        .user-native-checkbox:checked::before {
            transform: scale(1);
        }

        .user-native-checkbox:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .user-native-checkbox:focus-visible {
            outline: 2px solid rgba(22, 163, 74, .40) !important;
            outline-offset: 3px;
            box-shadow: none !important;
        }

        .user-availability-card {
            overflow: hidden;
        }

        .user-availability-control {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            width: 100%;
            padding: 1.05rem 1.15rem;
            cursor: pointer;
            user-select: none;
        }

        .user-availability-control:hover {
            background: #ffffff;
        }

        .user-availability-control .user-native-checkbox {
            margin-top: .18rem;
        }

        .user-availability-text {
            min-width: 0;
        }

        .user-availability-text .user-card-copy {
            display: block;
            margin-top: .25rem;
        }

        .user-actions {
            display: grid;
            gap: .65rem;
        }

        .user-actions .staff-button {
            width: 100%;
            justify-content: center;
        }



        .user-side-panel {
            border-color: #dbe4de;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        }

        .user-side-panel .user-card-head {
            align-items: center;
            gap: .75rem;
            padding: 1rem 1.15rem .75rem;
            border-bottom: 0;
        }

        .user-side-panel .user-card-icon {
            width: 2rem;
            height: 2rem;
            border-radius: .75rem;
            font-size: .8rem;
        }

        .user-side-panel .user-card-body {
            padding: 0 1.15rem 1.15rem;
        }

        .user-side-panel .user-card-copy {
            font-size: .82rem;
            line-height: 1.45;
        }

        .user-disclosure {
            border: 1px solid #e2e8f0;
            border-radius: .95rem;
            background: #f8fafc;
            overflow: hidden;
        }

        .user-disclosure summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            list-style: none;
            cursor: pointer;
            padding: .85rem .95rem;
            color: #0f172a;
            font-size: .86rem;
            font-weight: 850;
        }

        .user-disclosure summary::-webkit-details-marker {
            display: none;
        }

        .user-disclosure-hint {
            display: block;
            margin-top: .1rem;
            color: #64748b;
            font-size: .72rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .user-disclosure-chevron {
            color: #64748b;
            font-size: .72rem;
            transition: transform .16s ease;
        }

        .user-disclosure[open] .user-disclosure-chevron {
            transform: rotate(180deg);
        }

        .user-disclosure-panel {
            border-top: 1px solid #e2e8f0;
            padding: .95rem;
            background: #ffffff;
        }

        .user-rule-text {
            margin: 0;
            color: #475569;
            font-size: .8rem;
            line-height: 1.6;
        }

        .user-toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #f8fafc;
            padding: .9rem .95rem;
            transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
        }

        .user-toggle-card:hover {
            border-color: #bbf7d0;
            background: #ffffff;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .035);
        }


        .user-toggle-card-compact {
            align-items: center;
            padding: .85rem .95rem;
            min-height: 3.35rem;
        }

        .user-toggle-card-compact .user-toggle-title {
            margin: 0;
        }

        .user-toggle-title {
            display: block;
            color: #0f172a;
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .user-toggle-copy {
            display: block;
            margin-top: .25rem;
            color: #64748b;
            font-size: .76rem;
            line-height: 1.45;
        }

        .user-error {
            margin-top: .45rem;
            color: #b91c1c;
            font-size: .78rem;
            font-weight: 700;
        }

        @media (max-width: 1120px) {
            .user-editor-layout {
                grid-template-columns: 1fr;
            }

            .user-editor-side {
                position: static;
            }
        }

        @media (max-width: 720px) {
            .user-editor-summary {
                align-items: flex-start;
                flex-direction: column;
            }

            .user-editor-badges {
                justify-content: flex-start;
            }

            .user-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            <div class="mb-2 font-black">Please fix the following:</div>
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="user-editor-wrap space-y-6">

        <form method="POST" action="{{ route('staff.users.store') }}" class="space-y-5">
            @csrf

            <section class="user-editor-summary">
                <div class="user-editor-identity">
                    <div class="user-editor-avatar"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="min-w-0">
                        <p class="user-editor-kicker">New Authorized Account</p>
                        <h2 class="user-editor-title">Account Setup</h2>
                        <p class="user-editor-muted">Create login access, assign the correct role, and keep the account traceable.</p>
                    </div>
                </div>

                <div class="user-editor-badges">
                    <span class="staff-badge staff-badge-green">Audit Logged</span>
                </div>
            </section>

            <div class="user-editor-layout">
                <main class="user-editor-main">
                    <section class="user-card">
                        <div class="user-card-head">
                            <span class="user-card-icon"><i class="fa-solid fa-id-card"></i></span>
                            <div>
                                <h3 class="user-card-title">Login Information</h3>
                                <p class="user-card-copy">Enter the name and email address used for system login.</p>
                            </div>
                        </div>
                        <div class="user-card-body user-form-grid">
                            <div class="user-field">
                                <label class="user-label">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="user-input">
                                @error('name')
                                    <p class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="user-field">
                                <label class="user-label">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="user-input">
                                @error('email')
                                    <p class="user-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="user-card">
                        <div class="user-card-head">
                            <span class="user-card-icon"><i class="fa-solid fa-key"></i></span>
                            <div>
                                <h3 class="user-card-title">Initial Password</h3>
                                <p class="user-card-copy">Set the first password for this account.</p>
                            </div>
                        </div>
                        <div class="user-card-body user-form-grid">
                            <div class="user-field">
                                <label class="user-label">Password</label>
                                <input type="password" name="password" required class="user-input">
                                @error('password')
                                    <p class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="user-field">
                                <label class="user-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" required class="user-input">
                            </div>
                        </div>
                    </section>
                </main>

                <aside class="user-editor-side">
                    <section class="user-card user-side-panel">
                        <div class="user-card-head">
                            <span class="user-card-icon"><i class="fa-solid fa-shield-halved"></i></span>
                            <div>
                                <h3 class="user-card-title">Access Control</h3>
                                <p class="user-card-copy">Set the role first. Open landowner linking only when the account is for a landowner.</p>
                            </div>
                        </div>
                        <div class="user-card-body space-y-3">
                            <div class="user-field">
                                <label class="user-label">Role</label>
                                <select name="role" required class="user-select">
                                    <option value="">Select role</option>
                                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="landowner" {{ old('role') === 'landowner' ? 'selected' : '' }}>Landowner</option>
                                    <option value="geodetic" {{ old('role') === 'geodetic' ? 'selected' : '' }}>Geodetic</option>
                                </select>
                                @error('role')
                                    <p class="user-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <details class="user-disclosure" {{ old('role') === 'landowner' || old('landowner_id') ? 'open' : '' }}>
                                <summary>
                                    <span>
                                        Landowner link
                                        <span class="user-disclosure-hint">Required only for landowner accounts.</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down user-disclosure-chevron"></i>
                                </summary>
                                <div class="user-disclosure-panel">
                                    <div class="user-field">
                                        <label class="user-label">Linked Landowner Record</label>
                                        <select name="landowner_id" class="user-select">
                                            <option value="">No linked landowner record</option>
                                            @foreach ($landowners as $landowner)
                                                <option value="{{ $landowner->id }}" {{ (string) old('landowner_id') === (string) $landowner->id ? 'selected' : '' }}>
                                                    {{ $landowner->full_name }} — ID {{ $landowner->id }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('landowner_id')
                                            <p class="user-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </section>

                    <section class="user-card user-side-panel user-availability-card">
                        <div class="user-availability-control">
                            <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="user-native-checkbox">
                            <label for="is_active" class="user-availability-text cursor-pointer">
                                <span class="user-card-title">Active Account</span>
                                <span class="user-card-copy">Allow this user to access the system.</span>
                            </label>
                        </div>
                    </section>

                    <section class="user-card">
                        <div class="user-card-body">
                            <h3 class="user-card-title">Create Account</h3>
                            <p class="user-card-copy">Account creation is audit logged for traceability.</p>
                            <div class="user-actions mt-4">
                                <button type="submit" class="staff-button staff-button-primary">
                                    <i class="fa-solid fa-user-plus"></i>
                                    Create User
                                </button>
                                <a href="{{ route('staff.users.index') }}" class="staff-button staff-button-light">Cancel</a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>
</x-staff-shell>
