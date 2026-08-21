<x-staff-shell
    title="Staff Dashboard"
    active="dashboard"
    maxWidth="max-w-none"
>
    <x-slot name="styles">
        <style>
            .staff-dashboard {
                display: grid;
                gap: 18px;
            }

            .dashboard-hero {
                display: grid;
                grid-template-columns: minmax(0, 1.35fr) minmax(350px, 0.75fr);
                gap: 22px;
                align-items: center;
                padding: 20px 24px;
                border: 1px solid #145c38;
                border-radius: 18px;
                background: linear-gradient(135deg, #115a36 0%, #1a713f 100%);
                color: #ffffff;
                box-shadow: 0 12px 28px rgba(15, 81, 50, 0.14);
            }

            .hero-title {
                margin: 0;
                color: #ffffff;
                font-family: var(--heading-font);
                font-size: clamp(25px, 2.3vw, 32px);
                font-weight: 900;
                letter-spacing: -0.025em;
                line-height: 1.12;
            }

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 15px;
            }

            .hero-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 9px;
                min-height: 44px;
                padding: 8px 14px;
                border: 1px solid #ffffff;
                border-radius: 10px;
                background: #ffffff;
                color: #14532d;
                font-size: 12px;
                font-weight: 900;
                text-decoration: none;
                transition: transform 150ms ease, background 150ms ease;
            }

            .hero-action:hover {
                background: #f2fbf5;
                transform: translateY(-1px);
            }

            .hero-queue {
                padding: 11px 14px;
                border: 1px solid rgba(255, 255, 255, 0.18);
                border-radius: 14px;
                background: rgba(8, 47, 28, 0.34);
            }

            .hero-queue-title {
                margin: 0 0 7px;
                color: #ffffff;
                font-size: 12px;
                font-weight: 900;
            }

            .queue-row {
                display: grid;
                grid-template-columns: 32px minmax(0, 1fr) auto;
                align-items: center;
                gap: 10px;
                width: 100%;
                min-height: 44px;
                padding: 8px 4px;
                border: 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.13);
                background: transparent;
                color: inherit;
                font: inherit;
                text-align: left;
                cursor: pointer;
            }

            .queue-row:last-child { border-bottom: 0; }

            .queue-row.is-active {
                border-radius: 9px;
                background: rgba(255, 255, 255, 0.10);
            }

            .queue-row:focus-visible {
                outline: 2px solid #ffffff;
                outline-offset: 2px;
            }

            .queue-icon {
                display: grid;
                width: 29px;
                height: 29px;
                place-items: center;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.12);
                color: #ffffff;
                font-size: 11px;
            }

            .queue-label {
                display: block;
                color: #ffffff;
                font-size: 11px;
                font-weight: 900;
                line-height: 1.3;
            }

            .queue-description {
                display: block;
                margin-top: 2px;
                color: #ccebd7;
                font-size: 10px;
                line-height: 1.3;
            }

            .queue-value {
                color: #ffffff;
                font-family: var(--heading-font);
                font-size: 21px;
                font-weight: 900;
                line-height: 1;
            }

            .today-strip {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
            }

            .today-card {
                display: grid;
                grid-template-columns: 38px minmax(0, 1fr) auto;
                align-items: center;
                gap: 12px;
                min-height: 78px;
                padding: 14px 16px;
                border: 1px solid #d6e0da;
                border-radius: 13px;
                background: #ffffff;
                box-shadow: 0 3px 12px rgba(15, 23, 42, 0.045);
            }

            .today-icon {
                display: grid;
                width: 38px;
                height: 38px;
                place-items: center;
                border-radius: 10px;
                background: #eef8f1;
                color: #166534;
                font-size: 14px;
            }

            .today-label {
                color: #475569;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.35;
            }

            .today-value {
                color: #111827;
                font-family: var(--heading-font);
                font-size: 25px;
                font-weight: 900;
                line-height: 1;
            }

            .dashboard-workspace {
                display: grid;
                grid-template-columns: minmax(0, 1.9fr) minmax(320px, 0.7fr);
                gap: 18px;
                align-items: start;
            }

            .dashboard-panel {
                overflow: hidden;
                border: 1px solid #cfdcd4;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 7px 20px rgba(15, 23, 42, 0.055);
            }

            .panel-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 18px;
                padding: 17px 19px;
                border-bottom: 1px solid #e7ece9;
            }

            .panel-title {
                margin: 0;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 17px;
                font-weight: 900;
                letter-spacing: -0.01em;
            }

            .panel-subtitle {
                margin: 5px 0 0;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
            }

            .panel-link {
                color: #166534;
                font-size: 12px;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .panel-link:hover {
                text-decoration: underline;
                text-underline-offset: 3px;
            }

            .dashboard-table-wrap {
                overflow-x: auto;
                padding: 3px 19px 10px;
            }

            .dashboard-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .dashboard-table th {
                padding: 11px 8px;
                border-bottom: 1px solid #d9e1dc;
                color: #64748b;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.04em;
                text-align: left;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .dashboard-table td {
                padding: 13px 8px;
                border-bottom: 1px solid #edf1ee;
                color: #374151;
                vertical-align: middle;
            }

            .dashboard-table tbody tr:last-child td { border-bottom: 0; }
            .dashboard-table tbody tr:hover { background: #fbfdfb; }

            .application-link {
                color: #166534;
                font-weight: 900;
                text-decoration: none;
                white-space: nowrap;
            }

            .application-link:hover {
                text-decoration: underline;
                text-underline-offset: 3px;
            }

            .party-transfer {
                display: grid;
                gap: 3px;
                min-width: 220px;
            }

            .party-line {
                overflow: hidden;
                max-width: 420px;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .party-direction {
                color: #94a3b8;
                font-size: 9px;
            }

            .dashboard-status {
                display: inline-flex;
                align-items: center;
                padding: 5px 9px;
                border: 1px solid #cbd5e1;
                border-radius: 999px;
                background: #f1f5f9;
                color: #475569;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .status-released,
            .status-approved {
                border-color: #bbf7d0;
                background: #dcfce7;
                color: #166534;
            }

            .status-pending_legal_review,
            .status-pending_review,
            .status-draft {
                border-color: #fed7aa;
                background: #ffedd5;
                color: #c2410c;
            }

            .status-endorsed_lti,
            .status-endorsed_chief_legal,
            .status-endorsed_parpo,
            .status-for_releasing {
                border-color: #bfdbfe;
                background: #dbeafe;
                color: #1d4ed8;
            }

            .status-denied,
            .status-not_approved {
                border-color: #fecaca;
                background: #fee2e2;
                color: #b91c1c;
            }

            .attention-list {
                display: grid;
                gap: 0;
            }

            .attention-item {
                display: grid;
                grid-template-columns: 36px minmax(0, 1fr) auto;
                gap: 11px;
                align-items: center;
                min-height: 82px;
                padding: 14px 16px;
                border-bottom: 1px solid #edf1ee;
                color: inherit;
                text-decoration: none;
                transition: background-color 120ms ease;
            }

            .attention-item:last-child { border-bottom: 0; }
            .attention-item:hover { background: #f8faf9; }
            .attention-item.is-selected { background: #f0fdf4; }

            .attention-item:focus-visible {
                position: relative;
                z-index: 1;
                outline: 3px solid rgba(21, 128, 61, 0.24);
                outline-offset: -3px;
            }

            .attention-icon {
                display: grid;
                width: 34px;
                height: 34px;
                place-items: center;
                border-radius: 9px;
                background: #f1f5f9;
                color: #475569;
                font-size: 13px;
            }

            .attention-item.tone-warning .attention-icon {
                background: #fff7ed;
                color: #c2410c;
            }

            .attention-item.tone-success .attention-icon {
                background: #f0fdf4;
                color: #166534;
            }

            .attention-label {
                margin: 0;
                color: #0f172a;
                font-size: 13px;
                font-weight: 900;
                line-height: 1.3;
            }

            .attention-description {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 11.5px;
                line-height: 1.4;
            }

            .attention-action {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-top: 7px;
                color: #166534;
                font-size: 11px;
                font-weight: 900;
            }

            .attention-value {
                min-width: 36px;
                color: #111827;
                font-family: var(--heading-font);
                font-size: 24px;
                font-weight: 900;
                line-height: 1;
                text-align: right;
            }

            .attention-item.tone-warning .attention-value { color: #c2410c; }
            .attention-item.tone-success .attention-value { color: #166534; }

            .dashboard-empty {
                padding: 34px 18px;
                color: #6b7280;
                font-size: 12px;
                text-align: center;
            }

            @media (max-width: 1120px) {
                .dashboard-hero,
                .dashboard-workspace {
                    grid-template-columns: 1fr;
                }

                .hero-queue {
                    display: grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 1px;
                }

                .hero-queue-title { grid-column: 1 / -1; }

                .queue-row {
                    padding: 10px 8px;
                    border-right: 1px solid rgba(255, 255, 255, 0.13);
                    border-bottom: 0;
                }

                .queue-row:last-child { border-right: 0; }
            }

            @media (max-width: 760px) {
                .today-strip { grid-template-columns: 1fr; }
                .hero-queue { grid-template-columns: 1fr; }

                .queue-row {
                    border-right: 0;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.13);
                }

                .queue-row:last-child { border-bottom: 0; }
                .panel-header { flex-direction: column; }
            }

            @media (max-width: 560px) {
                .dashboard-hero {
                    padding: 20px 17px;
                    border-radius: 14px;
                }

                .hero-title { font-size: 26px; }
                .dashboard-table-wrap { padding-inline: 13px; }

                .attention-item {
                    grid-template-columns: 34px minmax(0, 1fr) auto;
                    padding-inline: 13px;
                }
            }
        </style>
    </x-slot>

    <div class="staff-dashboard">
        <section class="dashboard-hero" aria-label="Clearance operations">
            <div>
                <h2 class="hero-title">Welcome, {{ auth()->user()->name }}.</h2>

                <div class="hero-actions" aria-label="Primary staff actions">
                    <a href="{{ route('staff.applications.create') }}" class="hero-action">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Encode New Application
                    </a>
                </div>
            </div>

            <div class="hero-queue" aria-label="Current work queue">
                <h3 class="hero-queue-title">Current Work Queue</h3>

                @foreach ($workQueue as $item)
                    <button
                        type="button"
                        class="queue-row"
                        data-dashboard-filter="{{ $item['filter'] }}"
                        aria-pressed="false"
                    >
                        <span class="queue-icon">
                            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <span>
                            <span class="queue-label">{{ $item['label'] }}</span>
                            <span class="queue-description">{{ $item['description'] }}</span>
                        </span>
                        <span class="queue-value">{{ number_format($item['value']) }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="today-strip" aria-label="Today's office activity">
            @foreach ($todaySummary as $item)
                <article class="today-card">
                    <span class="today-icon">
                        <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="today-label">{{ $item['label'] }}</span>
                    <strong class="today-value">{{ number_format($item['value']) }}</strong>
                </article>
            @endforeach
        </section>

        <section class="dashboard-workspace">
            <article class="dashboard-panel" aria-label="Applications requiring action">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Applications Requiring Action</h2>
                        <p class="panel-subtitle">
                            @if ($attentionFocusLabel)
                                Filtered by {{ $attentionFocusLabel }}. Oldest updates are shown first for follow-up.
                            @else
                                Highest-priority active applications for continued staff processing.
                            @endif
                        </p>
                    </div>
                    @if ($attentionFocusLabel)
                        <a href="{{ route('staff.dashboard') }}" class="panel-link">Clear attention filter →</a>
                    @else
                        <a href="{{ route('staff.applications.index') }}" class="panel-link">View all applications →</a>
                    @endif
                </div>

                <div class="dashboard-table-wrap">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Transfer</th>
                                <th>Current Stage</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($actionApplications as $application)
                                <tr data-dashboard-status="{{ $application->status }}">
                                    <td>
                                        <a href="{{ route('staff.applications.show', $application) }}" class="application-link">
                                            {{ $application->application_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="party-transfer">
                                            <span class="party-line" title="{{ $application->transferorDisplayName() }}">
                                                {{ $application->transferorDisplayName() }}
                                            </span>
                                            <span class="party-direction">
                                                <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                            </span>
                                            <span class="party-line" title="{{ $application->transfereeDisplayName() }}">
                                                {{ $application->transfereeDisplayName() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="dashboard-status status-{{ $application->status }}">
                                            {{ $application->statusLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $application->updated_at?->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="dashboard-empty">
                                        {{ $attentionFocusLabel ? 'No active applications match this attention category.' : 'No active applications currently require staff action.' }}
                                    </td>
                                </tr>
                            @endforelse

                            @if ($actionApplications->isNotEmpty())
                                <tr data-dashboard-filter-empty hidden>
                                    <td colspan="4" class="dashboard-empty">No applications in this preview match the selected queue.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </article>

            <aside class="dashboard-panel" aria-label="Processing attention">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Processing Attention</h2>
                        <p class="panel-subtitle">Actionable groups for requirement work and follow-up.</p>
                    </div>
                </div>

                <div class="attention-list">
                    @foreach ($attentionItems as $item)
                        <a href="{{ $item['href'] }}"
                           class="attention-item tone-{{ $item['tone'] }}{{ $attentionFilter === $item['key'] ? ' is-selected' : '' }}"
                           @if ($attentionFilter === $item['key']) aria-current="true" @endif>
                            <span class="attention-icon">
                                <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span>
                                <span class="attention-label">{{ $item['label'] }}</span>
                                <span class="attention-description">{{ $item['description'] }}</span>
                                <span class="attention-action">
                                    {{ $item['action'] }}
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </span>
                            </span>
                            <strong class="attention-value">{{ number_format($item['value']) }}</strong>
                        </a>
                    @endforeach
                </div>
            </aside>
        </section>
    </div>

    <x-slot name="scripts">
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filterButtons = Array.from(document.querySelectorAll('[data-dashboard-filter]'));
                const applicationRows = Array.from(document.querySelectorAll('[data-dashboard-status]'));
                const emptyRow = document.querySelector('[data-dashboard-filter-empty]');

                if (!filterButtons.length || !applicationRows.length) {
                    return;
                }

                const activeWorkflowStatuses = ['endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo'];
                const pendingLegalStatuses = ['pending_legal_review', 'pending_review', 'draft'];

                filterButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const selectedFilter = button.dataset.dashboardFilter;
                        const wasActive = button.getAttribute('aria-pressed') === 'true';
                        let visibleCount = 0;

                        filterButtons.forEach(function (candidate) {
                            candidate.classList.remove('is-active');
                            candidate.setAttribute('aria-pressed', 'false');
                        });

                        if (!wasActive) {
                            button.classList.add('is-active');
                            button.setAttribute('aria-pressed', 'true');
                        }

                        applicationRows.forEach(function (row) {
                            const status = row.dataset.dashboardStatus;
                            const isVisible = wasActive
                                || (selectedFilter === 'active_workflow'
                                    ? activeWorkflowStatuses.includes(status)
                                    : (selectedFilter === 'pending_legal_review'
                                        ? pendingLegalStatuses.includes(status)
                                        : status === selectedFilter));

                            row.hidden = !isVisible;
                            if (isVisible) {
                                visibleCount += 1;
                            }
                        });

                        if (emptyRow) {
                            emptyRow.hidden = visibleCount !== 0;
                        }
                    });
                });
            });
        </script>
    </x-slot>
</x-staff-shell>
