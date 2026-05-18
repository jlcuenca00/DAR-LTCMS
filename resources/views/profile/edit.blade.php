@php
    $profileUser = $user ?? auth()->user();
    $isLandownerProfile = $profileUser?->isLandowner() ?? false;
    $isGeodeticProfile = $profileUser?->isGeodetic() ?? false;

    $profileContext = match (true) {
        $isLandownerProfile => [
            'portal' => 'Landowner Portal',
            'badge' => 'Own Records Account',
            'note' => 'Manage your login profile and password. This does not allow parcel editing, clearance processing, ownership transfer, or registry mutation.',
        ],
        $isGeodeticProfile => [
            'portal' => 'Geodetic Portal',
            'badge' => 'Read-only Account',
            'note' => 'Manage your login profile and password. Geodetic access remains limited to parcel and map reference review only.',
        ],
        default => [
            'portal' => 'Staff Workspace',
            'badge' => 'Staff Account',
            'note' => 'Manage your login profile and password. Role assignment and account status remain controlled through User Management.',
        ],
    };
@endphp

@push('styles')
    <style>
        .profile-stack {
            --profile-green-950: var(--lo-green-950, var(--geo-green-950, var(--dar-green, #14532d)));
            --profile-green-900: var(--lo-green-900, var(--geo-green-900, var(--dar-green, #14532d)));
            --profile-green-800: var(--lo-green-800, var(--geo-green-800, #166534));
            --profile-green-700: var(--lo-green-700, var(--geo-green-700, #15803d));
            --profile-green-50: var(--lo-green-50, var(--geo-green-50, #f0fdf4));
            --profile-ink: var(--lo-ink, var(--geo-ink, #07111f));
            --profile-muted: var(--lo-muted, var(--geo-muted, #667085));
            --profile-line: var(--lo-line, var(--geo-line, var(--border, #d7ded9)));
            display: grid;
            gap: 18px;
        }

        .profile-hero,
        .profile-panel {
            background: #ffffff;
            border: 1px solid var(--profile-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .profile-hero {
            padding: 22px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .profile-hero-main {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            min-width: 0;
        }

        .profile-hero-icon {
            width: 44px;
            height: 44px;
            border-radius: 11px;
            background: var(--profile-green-50);
            border: 1px solid #bbf7d0;
            color: var(--profile-green-800);
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            font-size: 18px;
        }

        .profile-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--profile-green-800);
        }

        .profile-title {
            margin: 5px 0 0;
            font-size: 24px;
            line-height: 1.15;
            font-weight: 900;
            color: var(--profile-ink);
            letter-spacing: -0.015em;
        }

        .profile-copy {
            margin: 8px 0 0;
            max-width: 760px;
            color: var(--profile-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .profile-badge {
            flex: 0 0 auto;
            border: 1px solid #bbf7d0;
            background: var(--profile-green-50);
            color: var(--profile-green-800);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 0.72fr);
            gap: 18px;
            align-items: start;
        }

        .profile-column {
            display: grid;
            gap: 18px;
        }

        .profile-panel {
            overflow: hidden;
        }

        .profile-panel-header {
            padding: 20px 22px 0;
        }

        .profile-panel-title {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: var(--profile-ink);
            letter-spacing: -0.01em;
        }

        .profile-panel-subtitle {
            margin: 5px 0 0;
            color: var(--profile-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .profile-panel-body {
            padding: 18px 22px 22px;
        }

        .profile-form {
            display: grid;
            gap: 15px;
        }

        .profile-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 16px;
        }

        .profile-field {
            display: grid;
            gap: 7px;
        }

        .profile-field.full { grid-column: 1 / -1; }

        .profile-label {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #344054;
        }

        .profile-input {
            width: 100%;
            min-height: 40px;
            border: 1px solid #cbd5d1;
            border-radius: 8px;
            padding: 9px 12px;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            outline: none;
            box-shadow: none;
        }

        .profile-input:focus {
            border-color: var(--profile-green-700);
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
        }

        .profile-error {
            color: #b91c1c;
            font-size: 12px;
            line-height: 1.45;
        }

        .profile-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 2px;
        }

        .profile-button {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            padding: 0 14px;
            border: 1px solid var(--profile-green-800);
            background: var(--profile-green-800);
            color: #ffffff;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            transition: 150ms ease;
        }

        .profile-button:hover {
            background: var(--profile-green-900);
            border-color: var(--profile-green-900);
        }

        .profile-button.secondary {
            background: #ffffff;
            color: var(--profile-green-900);
            border-color: #cbd5d1;
        }

        .profile-button.secondary:hover {
            background: #f8faf9;
            border-color: #bbd7c4;
        }

        .profile-saved {
            color: var(--profile-green-800);
            font-size: 12px;
            font-weight: 900;
        }

        .profile-note-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .profile-note-item {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fbfcfb;
            color: #344054;
            font-size: 13px;
            line-height: 1.5;
        }

        .profile-note-item i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--profile-green-50);
            color: var(--profile-green-800);
            border: 1px solid #bbf7d0;
            font-size: 12px;
        }

        .profile-verify-box {
            margin-top: 8px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12.5px;
            line-height: 1.5;
        }

        .profile-inline-button {
            border: 0;
            background: transparent;
            color: #166534;
            font: inherit;
            font-weight: 900;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }

        @media (max-width: 1120px) {
            .profile-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .profile-hero { flex-direction: column; }
            .profile-badge { align-self: flex-start; }
            .profile-form-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@if ($isGeodeticProfile)
    <x-geodetic-shell title="Profile Settings" active="profile">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-geodetic-shell>
@elseif ($isLandownerProfile)
    <x-landowner-shell title="Profile Settings" active="profile">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-landowner-shell>
@else
    <x-staff-shell title="Profile Settings" active="profile" maxWidth="max-w-5xl">
        @include('profile.partials.account-settings-content', ['user' => $profileUser, 'context' => $profileContext])
    </x-staff-shell>
@endif
