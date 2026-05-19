@props([
    'title' => 'Staff Workspace',
    'subtitle' => null,
    'active' => 'dashboard',
    'eyebrow' => 'DAR Negros Oriental Provincial Office',
    'maxWidth' => 'max-w-7xl',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | DAR-LTCMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    @isset($head)
        {{ $head }}
    @endisset

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @isset($styles)
        {{ $styles }}
    @endisset

    @stack('styles')

    <style>
        :root {
            --dar-green: #14532d;
            --dar-green-soft: #166534;
            --dar-green-bright: #15803d;
            --page-bg: #f3f4f6;
            --panel: #ffffff;
            --border: #d9dee5;
            --text: #111827;
            --muted: #6b7280;
            --heading-font: 'Google Sans';
            --body-font: 'Google Sans';
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: var(--body-font) !important;
            background: var(--page-bg);
            color: var(--text);
        }

        .staff-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            background: var(--page-bg);
        }

        .staff-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: #14532d;
            color: #d9fbe6;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.08);
            z-index: 20;
        }

        .staff-brand {
            min-height: 74px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .staff-brand-mark {
            height: 44px;
            width: 44px;
            border-radius: 12px;
            background: #ffffff;
            display: grid;
            place-items: center;
            padding: 6px;
            border: 1px solid rgba(255,255,255,0.24);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            flex: 0 0 auto;
        }

        .staff-brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .staff-brand-fallback {
            font-family: var(--heading-font);
            color: #14532d;
            font-size: 10px;
            font-weight: 900;
        }

        .staff-brand-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: #ffffff;
        }

        .staff-brand-subtitle {
            margin: 2px 0 0;
            font-size: 11px;
            color: #bbf7d0;
        }

        .staff-side-section {
            padding: 18px 14px;
        }

        .staff-side-label {
            padding: 0 10px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #86efac;
        }

        .staff-side-nav {
            display: grid;
            gap: 5px;
        }

        .staff-side-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 9px;
            color: #d9fbe6;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 160ms ease;
        }

        .staff-side-link:hover {
            background: rgba(255,255,255,0.10);
            color: white;
        }

        .staff-side-link.active {
            background: rgba(255,255,255,0.16);
            color: white;
            font-weight: 650;
        }

        .staff-side-link i {
            width: 20px;
            text-align: center;
            font-size: 15px;
            opacity: 0.95;
        }

        .staff-sidebar-footer {
            margin-top: auto;
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: grid;
            gap: 8px;
        }

        .staff-side-utility {
            display: grid;
            gap: 5px;
        }

        .staff-side-utility-label {
            padding: 0 10px;
            margin-bottom: 2px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #86efac;
        }

        .staff-footer-divider {
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 4px 0;
        }

        .staff-logout-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            background: transparent;
            color: #d9fbe6;
            padding: 10px 12px;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
        }

        .staff-logout-button:hover {
            background: rgba(255,255,255,0.10);
            color: white;
        }

        .staff-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .staff-topbar {
            min-height: 74px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 28px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .staff-page-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #166534;
        }

        .staff-page-title {
            margin: 4px 0 0;
            font-family: var(--heading-font);
            font-size: 22px;
            line-height: 1.1;
            font-weight: 900;
            color: #111827;
        }

        .staff-page-subtitle {
            margin: 4px 0 0;
            max-width: 760px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.45;
        }

        .staff-topbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }


        .notification-bell-link {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid #bbd7c4;
            background: #f0fdf4;
            color: #166534;
            display: inline-grid;
            place-items: center;
            text-decoration: none;
            font-size: 15px;
        }

        .notification-bell-link:hover {
            background: #dcfce7;
            color: #14532d;
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #dc2626;
            color: #ffffff;
            border: 2px solid #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
        }


        .notification-dropdown {
            position: relative;
            display: inline-block;
        }

        .notification-dropdown > summary {
            list-style: none;
            cursor: pointer;
        }

        .notification-dropdown > summary::-webkit-details-marker {
            display: none;
        }

        .notification-dropdown[open] .notification-bell-link {
            background: #dcfce7;
            color: #14532d;
            box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.12);
        }

        .notification-dropdown-panel {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            z-index: 80;
            width: min(380px, calc(100vw - 32px));
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid #d9dee5;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        }

        .notification-dropdown-panel::before {
            content: '';
            position: absolute;
            top: -7px;
            right: 18px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            border-left: 1px solid #d9dee5;
            border-top: 1px solid #d9dee5;
            background: #ffffff;
        }

        .notification-dropdown-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 16px 13px;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .notification-dropdown-kicker {
            margin: 0 0 3px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #15803d;
        }

        .notification-dropdown-header h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 900;
            color: #111827;
        }

        .notification-dropdown-count {
            flex: 0 0 auto;
            border-radius: 999px;
            background: #dcfce7;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 900;
            color: #166534;
        }

        .notification-dropdown-count.is-clear {
            background: #f1f5f9;
            color: #64748b;
        }

        .notification-dropdown-list {
            max-height: 310px;
            overflow-y: auto;
            background: #ffffff;
        }

        .notification-dropdown-item {
            display: grid;
            grid-template-columns: 10px minmax(0, 1fr);
            gap: 11px;
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: inherit;
            text-decoration: none;
            transition: 150ms ease;
        }

        .notification-dropdown-item:hover {
            background: #f8fafc;
        }

        .notification-dropdown-item.is-unread {
            background: #f0fdf4;
        }

        .notification-dropdown-item.is-unread:hover {
            background: #dcfce7;
        }

        .notification-dropdown-dot {
            width: 8px;
            height: 8px;
            margin-top: 6px;
            border-radius: 999px;
            background: #cbd5e1;
        }

        .notification-dropdown-item.is-unread .notification-dropdown-dot {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
        }

        .notification-dropdown-copy {
            min-width: 0;
        }

        .notification-dropdown-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .notification-dropdown-title-row strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.3;
        }

        .notification-dropdown-title-row i {
            margin-top: 2px;
            color: #94a3b8;
            font-size: 11px;
        }

        .notification-dropdown-copy p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 12px;
            font-weight: 650;
            line-height: 1.45;
        }

        .notification-dropdown-copy span {
            display: block;
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
        }

        .notification-dropdown-empty {
            display: grid;
            place-items: center;
            gap: 5px;
            padding: 26px 18px;
            text-align: center;
            color: #64748b;
        }

        .notification-dropdown-empty i {
            font-size: 20px;
            color: #15803d;
        }

        .notification-dropdown-empty strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .notification-dropdown-empty span {
            font-size: 12px;
            font-weight: 650;
        }

        .notification-see-all {
            display: flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-top: 1px solid #eef2f7;
            background: #ffffff;
            color: #166534;
            text-decoration: none;
            font-size: 13px;
            font-weight: 900;
        }

        .notification-see-all:hover {
            background: #f0fdf4;
            color: #14532d;
        }

        .staff-user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
        }

        .staff-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: #166534;
            color: white;
            display: grid;
            place-items: center;
            font-weight: 900;
        }

        .staff-content {
            padding: 26px 32px 40px;
        }

        .staff-content-inner {
            width: 100%;
            margin: 0 auto;
        }

        .staff-panel {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .staff-panel-pad { padding: 22px 24px; }

        .staff-panel-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 17px;
            font-weight: 900;
            color: #111827;
        }

        .staff-panel-subtitle {
            margin: 5px 0 0;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.55;
        }

        .staff-scope-banner {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #14532d;
            border-radius: 10px;
            padding: 14px 18px;
        }

        .staff-scope-banner h3 {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 900;
        }

        .staff-scope-banner p {
            margin: 5px 0 0;
            max-width: 920px;
            font-size: 12.5px;
            line-height: 1.55;
            font-weight: 600;
        }

        .staff-scope-pill {
            flex: 0 0 auto;
            border: 1px solid #bbf7d0;
            background: #dcfce7;
            color: #14532d;
            border-radius: 999px;
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .staff-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 40px;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            border: 1px solid transparent;
            transition: 150ms ease;
            cursor: pointer;
        }

        .staff-button-primary { background: #166534; color: #ffffff; }
        .staff-button-primary:hover { background: #14532d; }
        .staff-button-dark { background: #111827; color: #ffffff; }
        .staff-button-dark:hover { background: #000000; }
        .staff-button-light { background: #ffffff; color: #374151; border-color: #d1d5db; }
        .staff-button-light:hover { background: #f9fafb; }
        .staff-button-danger { background: #dc2626; color: #ffffff; }
        .staff-button-danger:hover { background: #b91c1c; }

        .staff-form-grid {
            display: grid;
            gap: 16px;
        }

        .staff-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .staff-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .staff-table th {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #d1d5db;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
            white-space: nowrap;
        }

        .staff-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }

        .staff-table tbody tr:hover td { background: #f9fafb; }

        .staff-link {
            color: #166534;
            font-weight: 800;
            text-decoration: none;
        }

        .staff-link:hover { text-decoration: underline; }

        .staff-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 900;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            color: #475569;
            white-space: nowrap;
        }

        .staff-badge-green { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .staff-badge-amber { background: #ffedd5; border-color: #fed7aa; color: #c2410c; }
        .staff-badge-red { background: #fee2e2; border-color: #fecaca; color: #b91c1c; }
        .staff-badge-blue { background: #dbeafe; border-color: #bfdbfe; color: #1d4ed8; }
        .staff-badge-slate { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }

        .staff-muted { color: #6b7280; }



        /* Consistent themed scrollbars across staff pages */
        html,
        .staff-table-wrap,
        .report-table-wrap,
        .table-wrap,
        .source-table-wrap,
        .timeline-table-wrap,
        .staff-scrollbar {
            scrollbar-color: #166534 #e5e7eb;
            scrollbar-width: thin;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 999px;
        }

        ::-webkit-scrollbar-thumb {
            background: #166534;
            border: 2px solid #e5e7eb;
            border-radius: 999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #14532d;
        }


        /* Staff filter forms: consistent column grid, not full-width stacked rows. */
        .staff-filter-grid,
        .staff-content-inner form.staff-filter-grid,
        .staff-content-inner section.staff-panel form[method="GET"] {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)) !important;
            gap: 14px 16px !important;
            align-items: end !important;
        }

        .staff-filter-field,
        .staff-filter-field-wide,
        .staff-content-inner section.staff-panel form[method="GET"] > div:not(.staff-filter-actions) {
            width: 100% !important;
            max-width: none !important;
            grid-column: auto !important;
        }

        .staff-filter-actions,
        .staff-content-inner section.staff-panel form[method="GET"] > .staff-filter-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            align-items: center !important;
            justify-content: flex-start !important;
            grid-column: 1 / -1 !important;
            padding-top: 2px !important;
        }

        .staff-filter-actions .staff-button,
        .staff-content-inner section.staff-panel form[method="GET"] .staff-button {
            min-height: 40px !important;
            height: 40px !important;
            padding: 0 14px !important;
        }

        @media (min-width: 1280px) {
            .staff-filter-grid.filter-grid-4,
            .staff-content-inner section.staff-panel form.staff-filter-grid.filter-grid-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }

            .staff-filter-grid.filter-grid-5,
            .staff-content-inner section.staff-panel form.staff-filter-grid.filter-grid-5 {
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }
        }

        @media (max-width: 760px) {
            .staff-filter-grid,
            .staff-content-inner form.staff-filter-grid,
            .staff-content-inner section.staff-panel form[method="GET"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* Themed pagination: override Laravel/Tailwind dark button group styling. */
        .staff-shell nav[role="navigation"],
        .staff-shell nav[aria-label="Pagination Navigation"] {
            display: flex !important;
            justify-content: center !important;
        }

        .staff-shell nav[role="navigation"] a,
        .staff-shell nav[role="navigation"] span,
        .staff-shell nav[role="navigation"] button,
        .staff-shell nav[aria-label="Pagination Navigation"] a,
        .staff-shell nav[aria-label="Pagination Navigation"] span,
        .staff-shell nav[aria-label="Pagination Navigation"] button,
        .staff-shell [aria-label="Pagination Navigation"] *[class*="bg-gray"],
        .staff-shell [aria-label="Pagination Navigation"] *[class*="bg-slate"],
        .staff-shell [aria-label="Pagination Navigation"] *[class*="text-gray"],
        .staff-shell [aria-label="Pagination Navigation"] *[class*="text-slate"] {
            background-color: #ffffff !important;
            color: #166534 !important;
            border-color: #bbd7c4 !important;
            box-shadow: none !important;
        }

        .staff-shell nav[role="navigation"] a:hover,
        .staff-shell nav[role="navigation"] button:hover,
        .staff-shell nav[aria-label="Pagination Navigation"] a:hover,
        .staff-shell nav[aria-label="Pagination Navigation"] button:hover {
            background-color: #f0fdf4 !important;
            color: #14532d !important;
            border-color: #86efac !important;
        }

        .staff-shell nav[role="navigation"] [aria-current="page"],
        .staff-shell nav[role="navigation"] [aria-current="page"] *,
        .staff-shell nav[aria-label="Pagination Navigation"] [aria-current="page"],
        .staff-shell nav[aria-label="Pagination Navigation"] [aria-current="page"] *,
        .staff-shell [aria-label="Pagination Navigation"] span[aria-current="page"],
        .staff-shell [aria-label="Pagination Navigation"] span[aria-current="page"] * {
            background-color: #166534 !important;
            color: #ffffff !important;
            border-color: #166534 !important;
            font-weight: 900 !important;
        }

        .staff-shell nav[role="navigation"] svg,
        .staff-shell nav[aria-label="Pagination Navigation"] svg {
            color: currentColor !important;
        }

        .staff-shell nav[role="navigation"] [aria-disabled="true"],
        .staff-shell nav[role="navigation"] [aria-disabled="true"] *,
        .staff-shell nav[aria-label="Pagination Navigation"] [aria-disabled="true"],
        .staff-shell nav[aria-label="Pagination Navigation"] [aria-disabled="true"] * {
            background-color: #f8fafc !important;
            color: #94a3b8 !important;
            border-color: #e5e7eb !important;
        }

        /* Better scrollbar visibility without black/native-looking bars. */
        .staff-shell * {
            scrollbar-color: #166534 #eef2f7;
            scrollbar-width: thin;
        }

        /* Better table internal alignment: first/last columns align with panel headers. */
        .staff-table th:first-child,
        .staff-table td:first-child {
            padding-left: 24px !important;
        }

        .staff-table th:last-child,
        .staff-table td:last-child {
            padding-right: 24px !important;
        }

        .staff-table-wrap {
            border-top: 1px solid #eef2f7;
        }

        /* Staff UI consistency helpers. These normalize older Blade sections that still carry Breeze/Tailwind page wrappers. */
        .staff-content-inner > .py-6,
        .staff-content-inner > div[class*="bg-gray-100"][class*="min-h-screen"],
        .staff-content-inner > div[class*="bg-gray-100"] {
            padding: 0 !important;
            background: transparent !important;
            min-height: auto !important;
        }

        .staff-content-inner > div > div[class*="max-w-"] {
            max-width: none !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .staff-content-inner .bg-white.shadow-sm,
        .staff-content-inner form.bg-white,
        .staff-content-inner section.bg-white {
            border-color: var(--border) !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
        }

        .staff-content-inner label {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #374151;
        }

        .staff-content-inner input[type="text"],
        .staff-content-inner input[type="email"],
        .staff-content-inner input[type="password"],
        .staff-content-inner input[type="number"],
        .staff-content-inner input[type="date"],
        .staff-content-inner input[type="search"],
        .staff-content-inner select,
        .staff-content-inner textarea {
            border-radius: 8px !important;
            border-color: #cbd5d1 !important;
            font-size: 14px !important;
            box-shadow: none !important;
        }

        .staff-content-inner input:focus,
        .staff-content-inner select:focus,
        .staff-content-inner textarea:focus {
            border-color: #15803d !important;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14) !important;
            outline: none !important;
        }

        .staff-content-inner .bg-green-100,
        .staff-content-inner .bg-green-50 {
            border-color: #bbf7d0 !important;
        }

        .staff-content-inner .bg-red-100,
        .staff-content-inner .bg-red-50 {
            border-color: #fecaca !important;
        }

        .staff-content-inner .font-heading,
        .staff-content-inner h1,
        .staff-content-inner h2,
        .staff-content-inner h3 {
            font-family: var(--heading-font) !important;
        }



        /* Font hierarchy: Google Sans only across the staff interface. */
        .staff-brand-title,
        .staff-side-label,
        .staff-side-utility-label,
        .staff-side-link,
        .staff-logout-button,
        .staff-page-eyebrow,
        .staff-page-title,
        .staff-panel-title,
        .staff-scope-banner h3,
        .staff-scope-pill,
        .staff-button,
        .staff-badge,
        .staff-link,
        .staff-table th,
        .staff-content-inner h1,
        .staff-content-inner h2,
        .staff-content-inner h3,
        .staff-content-inner h4,
        .staff-content-inner h5,
        .staff-content-inner h6,
        .staff-content-inner label,
        .staff-content-inner button,
        .staff-content-inner a[class*="button"],
        .staff-content-inner .font-bold,
        .staff-content-inner .font-semibold,
        .staff-content-inner .font-extrabold {
            font-family: var(--heading-font) !important;
        }

        .staff-brand-subtitle,
        .staff-page-subtitle,
        .staff-panel-subtitle,
        .staff-scope-banner p,
        .staff-muted,
        .staff-content-inner p,
        .staff-content-inner td,
        .staff-content-inner input,
        .staff-content-inner select,
        .staff-content-inner textarea,
        .staff-content-inner small {
            font-family: var(--body-font) !important;
        }

        .staff-page-title,
        .staff-panel-title,
        .staff-content-inner h1,
        .staff-content-inner h2,
        .staff-content-inner h3 {
            letter-spacing: -0.015em;
        }

        @media (max-width: 1180px) {
            .staff-shell { grid-template-columns: 230px minmax(0, 1fr); }
            .staff-topbar { align-items: flex-start; flex-direction: column; }
            .staff-topbar-actions { width: 100%; justify-content: flex-start; }
        }

        @media (max-width: 860px) {
            .staff-shell { grid-template-columns: 1fr; }
            .staff-sidebar { position: static; height: auto; }
            .staff-brand { min-height: 66px; }
            .staff-side-section { padding: 12px; }
            .staff-side-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .staff-sidebar-footer { padding: 12px; }
            .staff-side-utility { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .staff-topbar { position: static; padding: 18px; }
            .staff-content { padding: 18px; }
            .staff-page-title { font-size: 20px; }
            .staff-scope-banner { flex-direction: column; }
        }

        @media (max-width: 560px) {
            .staff-side-nav, .staff-side-utility { grid-template-columns: 1fr; }
            .staff-panel-pad { padding: 18px; }
            .staff-updated-chip, .staff-user-chip { width: 100%; }
            .staff-button { width: 100%; }
        }
    </style>
</head>
<body>
    @php
        $navItems = [
            ['active' => 'dashboard', 'route' => 'staff.dashboard', 'icon' => 'fa-gauge-high', 'label' => 'Staff Dashboard'],
            ['active' => 'applications', 'route' => 'staff.applications.index', 'icon' => 'fa-file-lines', 'label' => 'Applications'],
            ['active' => 'landowner-records', 'route' => 'staff.records.landowners.index', 'icon' => 'fa-users', 'label' => 'Landowner Records'],
            ['active' => 'parcel-records', 'route' => 'staff.records.parcels.index', 'icon' => 'fa-map-location-dot', 'label' => 'Parcel Records'],
            ['active' => 'parcel-map', 'route' => 'staff.parcel-map.index', 'icon' => 'fa-map', 'label' => 'Parcel Map'],
            ['active' => 'source-records', 'route' => 'staff.legacy-records.index', 'icon' => 'fa-box-archive', 'label' => 'Source Records'],
            ['active' => 'reports', 'route' => 'staff.reports.monitoring.index', 'icon' => 'fa-chart-column', 'label' => 'Monitoring Reports'],
            ['active' => 'audit-logs', 'route' => 'staff.audit-logs.index', 'icon' => 'fa-clipboard-list', 'label' => 'Audit Logs'],
        ];

        $utilityItems = [
            ['active' => 'users', 'route' => 'staff.users.index', 'icon' => 'fa-user-gear', 'label' => 'User Management'],
        ];

        $notificationUser = auth()->user();
        $notificationUnreadCount = $notificationUser?->unreadSystemNotifications()->count() ?? 0;
        $recentSystemNotifications = $notificationUser?->systemNotifications()->latest()->limit(5)->get() ?? collect();
    @endphp

    <div class="staff-shell">
        <aside class="staff-sidebar">
            <div class="staff-brand">
                <div class="staff-brand-mark">
                    @if (file_exists(public_path('images/dar-logo.png')))
                        <img src="{{ asset('images/dar-logo.png') }}" alt="Department of Agrarian Reform Logo" class="staff-brand-logo">
                    @elseif (file_exists(public_path('images/dar-logo.svg')))
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform Logo" class="staff-brand-logo">
                    @else
                        <span class="staff-brand-fallback">DAR</span>
                    @endif
                </div>
                <div>
                    <p class="staff-brand-title">DAR LTCMS</p>
                    <p class="staff-brand-subtitle">Negros Oriental</p>
                </div>
            </div>

            <div class="staff-side-section">
                <div class="staff-side-label">Staff Workspace</div>
                <nav class="staff-side-nav" aria-label="Staff navigation">
                    @foreach ($navItems as $item)
                        @if (\Illuminate\Support\Facades\Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               @class(['staff-side-link', 'active' => $active === $item['active']])>
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="staff-sidebar-footer">
                <div class="staff-side-utility-label">Administration</div>
                <nav class="staff-side-utility" aria-label="Staff administration navigation">
                    @foreach ($utilityItems as $item)
                        @if (\Illuminate\Support\Facades\Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               @class(['staff-side-link', 'active' => $active === $item['active']])>
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>

                <div class="staff-footer-divider"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="staff-logout-button">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="staff-main">
            <header class="staff-topbar">
                <div>
                    <p class="staff-page-eyebrow">{{ $eyebrow }}</p>
                    <h1 class="staff-page-title">{{ $title }}</h1>
                </div>

                <div class="staff-topbar-actions">

                    @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                        <details class="notification-dropdown">
                            <summary class="notification-bell-link" aria-label="Open recent notifications">
                                <i class="fa-solid fa-bell"></i>
                                @if ($notificationUnreadCount > 0)
                                    <span class="notification-badge">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
                                @endif
                            </summary>

                            @include('notifications.partials.panel', [
                                'notifications' => $recentSystemNotifications,
                                'unreadCount' => $notificationUnreadCount,
                            ])
                        </details>
                    @endif
                    {{ $actions ?? '' }}
                    <div class="staff-user-chip">
                        <div class="staff-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}</div>
                        <span>{{ auth()->user()->name ?? 'Staff User' }}</span>
                    </div>
                </div>
            </header>

            <div class="staff-content">
                <div class="staff-content-inner {{ $maxWidth }} space-y-5">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            document.querySelectorAll('.notification-dropdown[open]').forEach(function (dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.notification-dropdown[open]').forEach(function (dropdown) {
                    dropdown.removeAttribute('open');
                });
            }
        });
    </script>

    @isset($scripts)
        {{ $scripts }}
    @endisset

    @stack('scripts')
</body>
</html>
