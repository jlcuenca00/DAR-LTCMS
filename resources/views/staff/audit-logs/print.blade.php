<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Report | DAR-LTCMS</title>

    @php
        $generatedAtPh = $generatedAt?->timezone('Asia/Manila');
        $activeFilters = collect([
            'Action' => filled($filters['action'] ?? null)
                ? ucwords(str_replace('_', ' ', $filters['action']))
                : null,
            'Application' => $filters['application_code'] ?? null,
            'Actor' => $filters['actor'] ?? null,
        ])->filter();

        $recordLabel = function ($log): string {
            $recordType = class_basename($log->auditable_type) ?: 'System record';

            return $log->auditable_id
                ? $recordType . ' · ID ' . $log->auditable_id
                : $recordType;
        };
    @endphp

    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        * { box-sizing: border-box; }

        html { background: #e5e7eb; }

        body {
            margin: 0;
            color: #111827;
            background: #e5e7eb;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .print-toolbar {
            max-width: 1120px;
            margin: 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            color: #374151;
            font-size: 13px;
            font-weight: 800;
        }

        .toolbar-button {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            color: #111827;
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar-button.primary {
            border-color: #166534;
            background: #166534;
            color: #ffffff;
        }

        .report-page {
            width: 100%;
            max-width: 1120px;
            min-height: 190mm;
            margin: 0 auto 24px;
            padding: 14mm;
            border: 1px solid #d1d5db;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        }

        .report-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 10px;
            border-bottom: 3px double #14532d;
        }

        .agency {
            margin: 0;
            color: #14532d;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        h1 {
            margin: 4px 0 0;
            font-size: 21px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .report-copy {
            margin: 5px 0 0;
            color: #4b5563;
            font-size: 11.5px;
        }

        .record-count {
            flex: 0 0 auto;
            padding: 7px 10px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #14532d;
            font-size: 10.5px;
            font-weight: 900;
            white-space: nowrap;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 13px 0;
            border: 1px solid #d1d5db;
        }

        .meta-item {
            min-width: 0;
            padding: 8px 10px;
            border-right: 1px solid #d1d5db;
        }

        .meta-item:last-child { border-right: 0; }

        .meta-label {
            display: block;
            margin-bottom: 2px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .meta-value {
            color: #111827;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .filters {
            margin: 0 0 13px;
            padding: 8px 10px;
            border: 1px solid #dbe5dd;
            background: #f8faf9;
            color: #374151;
        }

        .filters strong { color: #14532d; }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        th {
            background: #edf5ef;
            color: #14532d;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        th:nth-child(1) { width: 14%; }
        th:nth-child(2) { width: 25%; }
        th:nth-child(3) { width: 18%; }
        th:nth-child(4) { width: 14%; }
        th:nth-child(5) { width: 17%; }
        th:nth-child(6) { width: 12%; }

        .primary { font-weight: 800; }
        .secondary { margin-top: 2px; color: #64748b; font-size: 10px; }
        .empty { padding: 24px; color: #64748b; text-align: center; }

        .report-note {
            margin: 12px 0 0;
            color: #64748b;
            font-size: 9.5px;
            line-height: 1.5;
        }

        @media print {
            html,
            body { background: #ffffff; }

            .print-toolbar { display: none !important; }

            .report-page {
                max-width: none;
                min-height: 0;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div class="toolbar-group">
            <a
                href="{{ route('staff.audit-logs.index', array_filter($filters ?? [])) }}"
                class="toolbar-button"
            >
                Return to Audit Logs
            </a>
            <span class="toolbar-title">Audit Log Report</span>
        </div>

        <button type="button" class="toolbar-button primary" onclick="window.print()">
            Print / Save as PDF
        </button>
    </div>

    <main class="report-page">
        <header class="report-header">
            <div>
                <p class="agency">DAR Negros Oriental Provincial Office</p>
                <h1>Audit Log Report</h1>
                <p class="report-copy">Read-only system activity history for accountability and traceability.</p>
            </div>
            <span class="record-count">{{ number_format($auditLogs->count()) }} record(s)</span>
        </header>

        <section class="meta-grid" aria-label="Report information">
            <div class="meta-item">
                <span class="meta-label">Generated On</span>
                <span class="meta-value">{{ $generatedAtPh?->format('M d, Y · h:i A') }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Generated By</span>
                <span class="meta-value">{{ $generatedBy?->name ?? 'Authorized DAR Staff' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Report Scope</span>
                <span class="meta-value">{{ $activeFilters->isEmpty() ? 'All audit records' : 'Filtered audit records' }}</span>
            </div>
        </section>

        <div class="filters">
            <strong>Applied filters:</strong>
            @if ($activeFilters->isEmpty())
                None
            @else
                {{ $activeFilters->map(fn ($value, $label) => $label . ': ' . $value)->implode(' · ') }}
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Activity</th>
                    <th>Actor</th>
                    <th>Application</th>
                    <th>Record</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $log)
                    <tr>
                        <td>
                            <div class="primary">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y') ?? 'N/A' }}</div>
                            <div class="secondary">{{ $log->created_at?->timezone('Asia/Manila')->format('h:i A') ?? '' }}</div>
                        </td>
                        <td>
                            <div class="primary">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                            @if (! empty($log->metadata))
                                <div class="secondary">Context metadata recorded</div>
                            @endif
                        </td>
                        <td>
                            <div class="primary">{{ $log->actor?->name ?? 'System' }}</div>
                            <div class="secondary">{{ $log->actor?->email ?? 'No user account' }}</div>
                        </td>
                        <td>
                            <div class="primary">{{ $log->application?->application_code ?? 'Not linked' }}</div>
                        </td>
                        <td>
                            <div class="primary">{{ $recordLabel($log) }}</div>
                        </td>
                        <td>
                            <div class="primary">{{ $log->ip_address ?: 'Not recorded' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">No audit records matched the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="report-note">
            Audit records are read-only system activity entries. This report supports administrative traceability and does not itself execute or confirm any land ownership transfer or registry mutation.
        </p>
    </main>
</body>
</html>
