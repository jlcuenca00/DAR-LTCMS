<x-staff-shell
    title="Source Package Details"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Source Records
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .source-detail-page {
                display: grid;
                gap: 18px;
            }

            .source-detail-hero {
                border: 1px solid #d8dee8;
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }

            .source-detail-hero-main {
                display: grid;
                grid-template-columns: minmax(0, 1.3fr) minmax(340px, 0.7fr);
                gap: 18px;
                padding: 20px 22px;
                align-items: stretch;
            }

            .source-detail-eyebrow,
            .source-detail-label {
                margin: 0 0 5px;
                color: #64748b;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.13em;
                text-transform: uppercase;
            }

            .source-detail-code {
                margin: 0;
                color: #0f172a;
                font-size: clamp(24px, 2.4vw, 34px);
                line-height: 1.05;
                font-weight: 950;
                letter-spacing: 0.05em;
                word-break: break-word;
            }

            .source-detail-subtitle {
                margin: 9px 0 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.5;
                max-width: 780px;
            }

            .source-detail-chip-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 12px;
            }

            .source-detail-hero-metrics {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .source-detail-metric {
                border: 1px solid #e2e8f0;
                border-radius: 13px;
                background: #f8fafc;
                padding: 13px 14px;
                min-height: 78px;
            }

            .source-detail-metric.is-green {
                border-color: #bbf7d0;
                background: #f0fdf4;
            }

            .source-detail-value {
                margin: 0;
                color: #0f172a;
                font-size: 17px;
                line-height: 1.2;
                font-weight: 950;
                overflow-wrap: anywhere;
            }

            .source-detail-metric.is-green .source-detail-value {
                color: #052e16;
            }

            .source-detail-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.12fr) minmax(430px, 0.88fr);
                gap: 18px;
                align-items: start;
            }

            .source-detail-stack {
                display: grid;
                gap: 14px;
                min-width: 0;
            }

            .source-detail-panel {
                border: 1px solid #dbe3ea;
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }

            .source-detail-panel-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 14px;
                padding: 17px 20px 14px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            }

            .source-detail-title {
                margin: 0;
                color: #0f172a;
                font-size: 16px;
                line-height: 1.25;
                font-weight: 950;
            }

            .source-detail-help {
                margin: 5px 0 0;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.45;
            }

            .source-detail-body {
                padding: 18px 20px 20px;
            }

            .source-detail-info-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .source-detail-info-card {
                min-width: 0;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #f8fafc;
                padding: 12px 13px;
            }

            .source-detail-info-card.is-wide {
                grid-column: span 2;
            }

            .source-detail-info-card.is-full {
                grid-column: 1 / -1;
            }

            .source-detail-info-value {
                margin: 0;
                color: #111827;
                font-size: 14px;
                line-height: 1.4;
                font-weight: 850;
                overflow-wrap: anywhere;
            }



            .source-file-proof-card {
                border: 1px solid #bbf7d0;
                background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
                border-radius: 16px;
                padding: 16px;
                display: grid;
                gap: 16px;
            }

            .source-file-proof-card.is-missing {
                border-color: #fed7aa;
                background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            }

            .source-file-proof-top {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: flex-start;
            }

            .source-file-proof-subtitle {
                margin-top: 5px;
                color: #64748b;
                font-size: 12.5px;
                line-height: 1.55;
                max-width: 560px;
            }

            .source-file-body-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) minmax(300px, 0.9fr);
                gap: 16px;
                align-items: stretch;
            }

            .source-file-missing-box {
                border: 1px dashed #fdba74;
                background: #fff7ed;
                color: #92400e;
                border-radius: 13px;
                padding: 14px;
                font-size: 12.5px;
                line-height: 1.55;
                display: flex;
                gap: 10px;
                align-items: flex-start;
            }

            .source-file-missing-box i {
                margin-top: 2px;
            }

            .source-file-preview {
                width: 100%;
                min-height: 220px;
                border: 1px solid #dbe4dd;
                background: #ffffff;
                border-radius: 14px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #166534;
                text-decoration: none;
            }

            .source-file-preview img {
                display: block;
                width: 100%;
                height: 100%;
                max-height: 280px;
                object-fit: cover;
            }

            .source-file-icon {
                width: 72px;
                height: 72px;
                border-radius: 20px;
                background: #ecfdf5;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 30px;
                box-shadow: inset 0 0 0 1px #bbf7d0;
            }

            .source-file-upload-box {
                border: 1px dashed #86efac;
                border-radius: 14px;
                background: #ffffff;
                padding: 15px;
                display: grid;
                gap: 9px;
            }

            .source-file-upload-box label {
                color: #14532d;
            }

            .source-file-upload-box input[type="file"] {
                width: 100%;
                font-size: 13px;
                color: #475569;
                cursor: pointer;
            }

            .source-file-upload-box input[type="file"]::file-selector-button {
                margin-right: 12px;
                border: 1px solid #166534;
                border-radius: 9px;
                background: #166534;
                color: #ffffff;
                padding: 9px 13px;
                font-size: 12px;
                font-weight: 900;
                cursor: pointer;
                transition: 150ms ease;
            }

            .source-file-upload-box input[type="file"]::file-selector-button:hover {
                background: #14532d;
                border-color: #14532d;
            }

            .source-file-actions-grid {
                display: grid;
                gap: 9px;
                align-content: start;
            }

            .source-file-actions-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .source-file-actions-row form {
                margin: 0;
            }

            .source-file-proof-card .staff-button {
                min-height: 42px;
            }

            @media (max-width: 1100px) {
                .source-detail-grid,
                .source-file-body-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 640px) {
                .source-file-actions-row {
                    grid-template-columns: 1fr;
                }
            }

            .source-detail-text-box {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #ffffff;
                padding: 13px 14px;
                color: #334155;
                font-size: 13px;
                line-height: 1.55;
                white-space: pre-line;
            }

            .source-linkage-grid {
                display: grid;
                grid-template-columns: minmax(280px, 0.8fr) minmax(0, 1.2fr);
                gap: 16px;
                align-items: start;
            }

            .source-link-action-stack {
                display: grid;
                gap: 14px;
                min-width: 0;
            }

            .source-link-card {
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #f8fafc;
                padding: 16px;
                min-width: 0;
            }

            .source-link-card.is-form {
                background: #ffffff;
            }

            .source-link-card.is-reference {
                background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            }

            .source-create-card {
                background: linear-gradient(180deg, #f8fffb 0%, #ffffff 100%);
                border-color: #bbf7d0;
            }

            .source-create-card .source-detail-form {
                margin-top: 14px;
                padding-top: 14px;
                border-top: 1px solid #e5e7eb;
            }

            .source-create-card .source-form-grid,
            .source-create-card .source-form-grid.three {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .source-create-card .source-form-field.full {
                grid-column: 1 / -1;
            }

            .source-create-summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-height: 44px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #14532d;
                border-radius: 12px;
                padding: 10px 12px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 950;
            }

            .source-create-summary::-webkit-details-marker {
                display: none;
            }

            .source-create-summary::after {
                content: '\f078';
                font-family: 'Font Awesome 6 Free';
                font-weight: 900;
                font-size: 11px;
                transition: transform 160ms ease;
            }

            details[open] > .source-create-summary::after {
                transform: rotate(180deg);
            }

            .source-file-actions-grid .source-detail-info-card {
                background: #f8fafc;
            }

            .source-file-actions-grid .source-detail-help {
                overflow-wrap: anywhere;
            }

            .source-mini-title {
                margin: 0;
                color: #0f172a;
                font-size: 14px;
                font-weight: 950;
            }

            .source-mini-copy {
                margin: 5px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
            }

            .source-detail-form {
                display: grid;
                gap: 12px;
                margin-top: 14px;
            }

            .source-form-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .source-form-grid.three {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .source-form-field {
                display: grid;
                gap: 5px;
                min-width: 0;
            }

            .source-form-field.full {
                grid-column: 1 / -1;
            }

            .source-form-field label {
                color: #334155;
                font-size: 10.5px;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .source-input {
                width: 100%;
                min-height: 40px;
                border: 1px solid #cbd5e1;
                border-radius: 11px;
                background: #ffffff;
                color: #0f172a;
                font-size: 13.5px;
                padding: 9px 11px;
            }

            .source-input:focus {
                border-color: #15803d;
                outline: none;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.13);
            }

            .source-detail-record-list {
                display: grid;
                gap: 10px;
            }

            .source-detail-record-card {
                display: grid;
                grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) auto;
                gap: 12px;
                align-items: center;
                border: 1px solid #e5e7eb;
                border-radius: 14px;
                background: #ffffff;
                padding: 13px 14px;
            }

            .source-record-title {
                margin: 0;
                color: #0f172a;
                font-size: 14px;
                font-weight: 950;
            }

            .source-record-meta {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 12px;
                line-height: 1.45;
            }

            .source-reference-list {
                color: #334155;
                font-size: 12.5px;
                line-height: 1.5;
            }

            .source-empty-state {
                border: 1px dashed #cbd5e1;
                border-radius: 14px;
                background: #f8fafc;
                padding: 18px;
                color: #64748b;
                font-size: 13px;
                text-align: center;
            }

            .source-parcel-actions {
                display: grid;
                grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
                gap: 14px;
            }

            @media (max-width: 1180px) {
                .source-detail-hero-main,
                .source-detail-grid,
                .source-linkage-grid,
                .source-parcel-actions {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 760px) {
                .source-detail-info-grid,
                .source-form-grid,
                .source-form-grid.three,
                .source-create-card .source-form-grid,
                .source-create-card .source-form-grid.three,
                .source-detail-hero-metrics,
                .source-detail-record-card {
                    grid-template-columns: 1fr;
                }

                .source-detail-info-card.is-wide,
                .source-form-field.full {
                    grid-column: 1 / -1;
                }

                .source-detail-panel-header {
                    flex-direction: column;
                }
            }
        </style>
    </x-slot>

    @php
        $sourceLandownerName = $package->landowner_name ?: ($package->transferor_name ?: $package->transferee_name);
        $nameParts = $sourceLandownerName ? preg_split('/\s+/', trim($sourceLandownerName)) : [];
        $suggestedFirstName = $nameParts[0] ?? '';
        $suggestedLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        $recordCount = $package->records->count();
        $sourceFileExists = $package->source_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($package->source_file_path);
        $sourceFileUrl = $sourceFileExists ? \Illuminate\Support\Facades\Storage::disk('public')->url($package->source_file_path) : null;
        $sourceFileMime = $package->source_file_mime_type;
        $sourceFileIsImage = $sourceFileMime && str_starts_with((string) $sourceFileMime, 'image/');
        $sourceFileIsPdf = $sourceFileMime === 'application/pdf';
    @endphp

    <div class="source-detail-page">
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-black">Please correct the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="source-detail-hero">
            <div class="source-detail-hero-main">
                <div>
                    <p class="source-detail-eyebrow">Source Record Package</p>
                    <h2 class="source-detail-code">{{ $package->package_code }}</h2>
                    <p class="source-detail-subtitle">
                        Grouped documentary/reference information used for parcel matching, landholding review, source traceability, and source-to-main-record linkage.
                    </p>
                    <div class="source-detail-chip-row">
                        <span class="staff-badge staff-badge-green">{{ $package->status_label }}</span>
                        <span class="staff-badge staff-badge-slate">{{ $package->source_record_scope_label }}</span>
                        <span class="staff-badge {{ $package->parcel ? 'staff-badge-green' : 'staff-badge-slate' }}">
                            {{ $package->parcel ? 'Linked Parcel' : 'Unlinked Parcel' }}
                        </span>
                        <span class="staff-badge {{ $package->source_file_status_class }}">
                            {{ $package->source_file_status_label }}
                        </span>
                    </div>
                </div>

                <div class="source-detail-hero-metrics">
                    <div class="source-detail-metric is-green">
                        <p class="source-detail-label">Records Created</p>
                        <p class="source-detail-value">{{ $recordCount }}</p>
                    </div>
                    <div class="source-detail-metric">
                        <p class="source-detail-label">Parcel Ref</p>
                        <p class="source-detail-value">{{ $package->parcel_code ?? 'N/A' }}</p>
                    </div>
                    <div class="source-detail-metric">
                        <p class="source-detail-label">Area</p>
                        <p class="source-detail-value">{{ $package->area_hectares ? $package->area_hectares . ' ha' : 'N/A' }}</p>
                    </div>
                    <div class="source-detail-metric">
                        <p class="source-detail-label">Source</p>
                        <p class="source-detail-value">{{ $package->source_book ?? 'N/A' }}</p>
                    </div>
                    <div class="source-detail-metric">
                        <p class="source-detail-label">Digitization</p>
                        <p class="source-detail-value">{{ $package->has_source_file ? 'File Attached' : 'Needs File' }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="source-detail-grid">
            <main class="source-detail-stack">
                <section class="source-detail-panel">
                    <div class="source-detail-panel-header">
                        <div>
                            <h3 class="source-detail-title">Package Information</h3>
                            <p class="source-detail-help">Main encoded values from the source document or source package.</p>
                        </div>
                    </div>
                    <div class="source-detail-body">
                        <div class="source-detail-info-grid">
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Record Coverage</p>
                                <p class="source-detail-info-value">{{ $package->source_record_scope_label }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Title Number</p>
                                <p class="source-detail-info-value">{{ $package->title_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Landholding Ref</p>
                                <p class="source-detail-info-value">{{ $package->landholding_reference_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Clearance Control</p>
                                <p class="source-detail-info-value">{{ $package->control_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Lot Number</p>
                                <p class="source-detail-info-value">{{ $package->lot_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Survey Number</p>
                                <p class="source-detail-info-value">{{ $package->survey_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Landowner / Owner</p>
                                <p class="source-detail-info-value">{{ $package->landowner_name ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Transferor</p>
                                <p class="source-detail-info-value">{{ $package->transferor_name ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Transferee</p>
                                <p class="source-detail-info-value">{{ $package->transferee_name ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Barangay</p>
                                <p class="source-detail-info-value">{{ $package->barangay ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Municipality</p>
                                <p class="source-detail-info-value">{{ $package->municipality ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Province</p>
                                <p class="source-detail-info-value">{{ $package->province ?? 'Negros Oriental' }}</p>
                            </div>

                            @if ($package->remarks)
                                <div class="source-detail-info-card is-full">
                                    <p class="source-detail-label">Remarks</p>
                                    <div class="source-detail-text-box">{{ $package->remarks }}</div>
                                </div>
                            @endif

                            @if ($package->boundary_description)
                                <div class="source-detail-info-card is-full">
                                    <p class="source-detail-label">Boundary / Technical Description</p>
                                    <div class="source-detail-text-box">{{ $package->boundary_description }}</div>
                                </div>
                            @endif

                            @if ($package->source_notes)
                                <div class="source-detail-info-card is-full">
                                    <p class="source-detail-label">Source Notes</p>
                                    <div class="source-detail-text-box">{{ $package->source_notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="source-detail-panel">
                    <div class="source-detail-panel-header">
                        <div>
                            <p class="source-detail-eyebrow">Landowner Record Linkage</p>
                            <h3 class="source-detail-title">Source Party to Main Landowner Record</h3>
                            <p class="source-detail-help">Choose an existing landowner, or create one from the encoded source name when no match exists. This is an administrative reference link only.</p>
                        </div>
                        <span class="staff-badge {{ $package->landowner ? 'staff-badge-green' : 'staff-badge-slate' }}">
                            {{ $package->landowner ? 'Linked' : 'Not Linked' }}
                        </span>
                    </div>
                    <div class="source-detail-body">
                        <div class="source-linkage-grid">
                            <div class="source-link-card is-reference">
                                <h4 class="source-mini-title">Source Name Reference</h4>
                                <div class="source-detail-info-grid mt-3" style="grid-template-columns: 1fr;">
                                    <div>
                                        <p class="source-detail-label">Encoded Owner</p>
                                        <p class="source-detail-info-value">{{ $package->landowner_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="source-detail-label">Transferor</p>
                                        <p class="source-detail-info-value">{{ $package->transferor_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="source-detail-label">Transferee</p>
                                        <p class="source-detail-info-value">{{ $package->transferee_name ?? 'N/A' }}</p>
                                    </div>
                                </div>

                                @if ($package->landowner)
                                    <div class="mt-4 rounded-lg border border-green-200 bg-white p-3 text-sm">
                                        <p class="source-detail-label">Currently Linked To</p>
                                        <p class="mt-1 font-black text-green-800">{{ $package->landowner->full_name }}</p>
                                        <p class="text-gray-600">{{ $package->landowner->barangay ?? 'N/A' }}, {{ $package->landowner->municipality ?? 'N/A' }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="source-link-action-stack">
                                <div class="source-link-card is-form">
                                    <h4 class="source-mini-title">Link Existing Landowner</h4>
                                    <p class="source-mini-copy">Use this when the correct person already exists in Landowner Records.</p>

                                <form method="POST" action="{{ route('staff.source-record-packages.link-landowner', $package) }}" class="source-detail-form">
                                    @csrf
                                    <div class="source-form-field">
                                        <label>Existing Landowner Record</label>
                                        <select name="landowner_id" required class="source-input">
                                            <option value="">Select landowner</option>
                                            @foreach ($landowners as $landowner)
                                                <option value="{{ $landowner->id }}" @selected($package->landowner_id === $landowner->id)>
                                                    {{ $landowner->full_name }}
                                                    @if ($landowner->barangay || $landowner->municipality)
                                                        — {{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="staff-button staff-button-primary justify-center">
                                        Link Existing Landowner
                                    </button>
                                </form>
                            </div>

                            <div class="source-link-card is-form source-create-card">
                                <h4 class="source-mini-title">Create Landowner From Source</h4>
                                <p class="source-mini-copy">Use this only when no matching Landowner Record exists yet. The created record is linked for review and traceability.</p>

                                <details class="mt-4">
                                    <summary class="source-create-summary"><span><i class="fa-solid fa-user-plus mr-2"></i> Open creation form</span></summary>
                                    <form method="POST" action="{{ route('staff.source-record-packages.create-landowner', $package) }}" class="source-detail-form">
                                        @csrf
                                        <div class="source-form-grid">
                                            <div class="source-form-field">
                                                <label>First Name *</label>
                                                <input name="first_name" required value="{{ old('first_name', $suggestedFirstName) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Last Name *</label>
                                                <input name="last_name" required value="{{ old('last_name', $suggestedLastName) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Middle Name</label>
                                                <input name="middle_name" value="{{ old('middle_name') }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Suffix</label>
                                                <input name="suffix" value="{{ old('suffix') }}" class="source-input">
                                            </div>
                                            <div class="source-form-field full">
                                                <label>Address Line</label>
                                                <input name="address_line" value="{{ old('address_line') }}" class="source-input">
                                            </div>
                                        </div>
                                        <div class="source-form-grid three">
                                            <div class="source-form-field">
                                                <label>Barangay</label>
                                                <input name="barangay" value="{{ old('barangay', $package->barangay) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Municipality</label>
                                                <input name="municipality" value="{{ old('municipality', $package->municipality) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Province</label>
                                                <input name="province" value="{{ old('province', $package->province ?? 'Negros Oriental') }}" class="source-input">
                                            </div>
                                        </div>
                                        <div class="source-form-field">
                                            <label>Contact Number</label>
                                            <input name="contact_number" value="{{ old('contact_number') }}" class="source-input">
                                        </div>
                                        <button type="submit" class="staff-button staff-button-dark justify-center">
                                            Create and Link Landowner
                                        </button>
                                    </form>
                                </details>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="source-detail-panel">
                    <div class="source-detail-panel-header">
                        <div>
                            <h3 class="source-detail-title">Source Records Created From This Package</h3>
                            <p class="source-detail-help">Generated source entries grouped under this package.</p>
                        </div>
                        <span class="staff-badge staff-badge-slate">{{ $recordCount }} record(s)</span>
                    </div>
                    <div class="source-detail-body">
                        @if ($package->records->isEmpty())
                            <div class="source-empty-state">No source records found for this package.</div>
                        @else
                            <div class="source-detail-record-list">
                                @foreach ($package->records as $record)
                                    <article class="source-detail-record-card">
                                        <div>
                                            <p class="source-record-title">{{ $record->record_type_label }}</p>
                                            <p class="source-record-meta">{{ $record->origin_label }} · {{ $record->source_book ?? 'N/A' }}</p>
                                        </div>
                                        <div class="source-reference-list">
                                            @if ($record->title_number)
                                                <div><strong>Title:</strong> {{ $record->title_number }}</div>
                                            @endif
                                            @if ($record->parcel_code)
                                                <div><strong>Parcel Ref:</strong> {{ $record->parcel_code }}</div>
                                            @endif
                                            @if ($record->landholding_reference_number)
                                                <div><strong>Landholding:</strong> {{ $record->landholding_reference_number }}</div>
                                            @endif
                                            @if ($record->control_number)
                                                <div><strong>Control:</strong> {{ $record->control_number }}</div>
                                            @endif
                                            @if (! $record->title_number && ! $record->parcel_code && ! $record->landholding_reference_number && ! $record->control_number)
                                                N/A
                                            @endif
                                        </div>
                                        <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                            View
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                @if (! $package->parcel)
                    <section class="source-detail-panel">
                        <div class="source-detail-panel-header">
                            <div>
                                <h3 class="source-detail-title">Parcel Record Linkage</h3>
                                <p class="source-detail-help">Connect this source package to an existing parcel or create a new main parcel record from reviewed source values.</p>
                            </div>
                        </div>
                        <div class="source-detail-body">
                            <div class="source-parcel-actions">
                                <div class="source-link-card is-form">
                                    <h4 class="source-mini-title">Link Existing Parcel</h4>
                                    <p class="source-mini-copy">Use this if the parcel already exists in the main Parcel Records module.</p>

                                    <form method="POST" action="{{ route('staff.source-record-packages.link-parcel', $package) }}" class="source-detail-form">
                                        @csrf
                                        <div class="source-form-field">
                                            <label>Existing Parcel</label>
                                            <select name="parcel_id" class="source-input">
                                                <option value="">Select parcel</option>
                                                @foreach ($parcels as $parcel)
                                                    <option value="{{ $parcel->id }}">
                                                        {{ $parcel->parcel_code }}
                                                        @if ($parcel->title_no) — {{ $parcel->title_no }} @endif
                                                        @if ($parcel->barangay || $parcel->municipality) — {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="staff-button staff-button-primary justify-center">
                                            Link Package
                                        </button>
                                    </form>
                                </div>

                                <div class="source-link-card is-form">
                                    <h4 class="source-mini-title">Create Main Parcel Record From Package</h4>
                                    <p class="source-mini-copy">Use this after staff confirms that the source package should become a main Parcel Record entry.</p>

                                    <form method="POST" action="{{ route('staff.source-record-packages.create-parcel', $package) }}" class="source-detail-form">
                                        @csrf
                                        <div class="source-form-grid">
                                            <div class="source-form-field">
                                                <label>Parcel Code *</label>
                                                <input name="parcel_code" value="{{ old('parcel_code', $package->parcel_code) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Title Number</label>
                                                <input name="title_no" value="{{ old('title_no', $package->title_number) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Area</label>
                                                <input type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares', $package->area_hectares) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Status *</label>
                                                <select name="status" class="source-input">
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                            <div class="source-form-field">
                                                <label>Barangay</label>
                                                <input name="barangay" value="{{ old('barangay', $package->barangay) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Municipality</label>
                                                <input name="municipality" value="{{ old('municipality', $package->municipality) }}" class="source-input">
                                            </div>
                                            <div class="source-form-field full">
                                                <label>Link Existing Landowner as Active Landholding</label>
                                                <select name="landowner_id" class="source-input">
                                                    <option value="">Do not link landowner yet</option>
                                                    @foreach ($landowners as $landowner)
                                                        <option value="{{ $landowner->id }}">
                                                            {{ $landowner->full_name }}
                                                            @if ($landowner->barangay || $landowner->municipality)
                                                                — {{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }}
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <p class="source-mini-copy">The map displays owner names through active landholding records.</p>
                                            </div>
                                            <div class="source-form-field">
                                                <label>Date Acquired</label>
                                                <input type="date" name="date_acquired" value="{{ old('date_acquired') }}" class="source-input">
                                            </div>
                                            <div class="source-form-field">
                                                <label>Province</label>
                                                <input name="province" value="{{ old('province', $package->province ?? 'Negros Oriental') }}" class="source-input">
                                            </div>
                                            <div class="source-form-field full">
                                                <label>Parcel GeoJSON Geometry</label>
                                                <textarea name="geometry_geojson" rows="6" class="source-input font-mono text-xs">{{ old('geometry_geojson', $package->source_geometry_geojson ? json_encode($package->source_geometry_geojson) : '') }}</textarea>
                                                <p class="source-mini-copy">Only main Parcel Records with saved geometry appear on the Parcel Map Viewer.</p>
                                            </div>
                                            <div class="source-form-field full">
                                                <label>Remarks</label>
                                                <textarea name="remarks" rows="3" class="source-input">{{ old('remarks', 'Created from source package ' . $package->package_code . '.') }}</textarea>
                                            </div>
                                        </div>
                                        <button type="submit" class="staff-button staff-button-dark justify-center">
                                            Create Parcel Record
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="source-detail-stack">
                <section class="source-detail-panel">
                    <div class="source-detail-panel-header">
                        <div>
                            <h3 class="source-detail-title">Source / Provenance</h3>
                            <p class="source-detail-help">Where this encoded reference information came from.</p>
                        </div>
                    </div>
                    <div class="source-detail-body">
                        <div class="source-detail-info-grid" style="grid-template-columns: 1fr;">
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Source Book / File</p>
                                <p class="source-detail-info-value">{{ $package->source_book ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Page Number</p>
                                <p class="source-detail-info-value">{{ $package->page_number ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Transcribed By</p>
                                <p class="source-detail-info-value">{{ $package->transcribed_by ?? 'N/A' }}</p>
                            </div>
                            <div class="source-detail-info-card">
                                <p class="source-detail-label">Transcription Date</p>
                                <p class="source-detail-info-value">{{ $package->transcription_date?->format('F d, Y') ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 source-file-proof-card {{ $package->has_source_file ? '' : 'is-missing' }}">
                            <div class="source-file-proof-top">
                                <div>
                                    <p class="source-detail-label">Source Scan / Reference File</p>
                                    <p class="source-detail-info-value">{{ $package->has_source_file ? ($package->source_file_original_filename ?? 'Attached source file') : 'No source file attached yet' }}</p>
                                    <p class="source-file-proof-subtitle">
                                        Attach or replace the scanned document, PDF, or image used as the basis of this source package. This supports traceability but does not legally verify ownership or mutate registry records.
                                    </p>
                                </div>
                                <span class="staff-badge {{ $package->source_file_status_class }}">{{ $package->source_file_status_label }}</span>
                            </div>

                            @if ($sourceFileExists)
                                <div class="source-file-body-grid">
                                    <a href="{{ $sourceFileUrl }}" target="_blank" rel="noopener" class="source-file-preview" aria-label="Open source file">
                                        @if ($sourceFileIsImage)
                                            <img src="{{ $sourceFileUrl }}" alt="Preview of {{ $package->source_file_original_filename }}">
                                        @else
                                            <span class="source-file-icon">
                                                <i class="fa-solid {{ $sourceFileIsPdf ? 'fa-file-pdf' : 'fa-file-lines' }}"></i>
                                            </span>
                                        @endif
                                    </a>

                                    <div class="source-file-actions-grid">
                                        <div class="source-detail-info-card">
                                            <p class="source-detail-label">File Details</p>
                                            <p class="source-detail-help mt-1">
                                                <strong>Type:</strong> {{ $package->source_file_mime_type ?? 'Unknown file type' }}<br>
                                                <strong>Uploaded by:</strong> {{ optional($package->sourceFileUploadedBy)->name ?? 'Unknown user' }}<br>
                                                <strong>Uploaded at:</strong> {{ $package->source_file_uploaded_at?->format('M d, Y h:i A') ?? 'N/A' }}
                                            </p>
                                        </div>

                                        <div class="source-file-actions-row">
                                            <a href="{{ $sourceFileUrl }}" target="_blank" rel="noopener" class="staff-button staff-button-light justify-center">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                Open Source File
                                            </a>

                                            <form method="POST" action="{{ route('staff.source-record-packages.source-file.destroy', $package) }}" onsubmit="return confirm('Remove the attached source file from this package?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="staff-button staff-button-danger w-full justify-center">
                                                    <i class="fa-solid fa-trash"></i>
                                                    Remove File
                                                </button>
                                            </form>
                                        </div>

                                        <form method="POST" action="{{ route('staff.source-record-packages.source-file.store', $package) }}" enctype="multipart/form-data" class="source-detail-form">
                                            @csrf
                                            <div class="source-file-upload-box">
                                                <label class="block text-xs font-black uppercase tracking-wide">Replace source file</label>
                                                <input type="file" name="source_file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                                <p class="mt-1 text-xs text-gray-500">Accepted files: PDF, JPG, JPEG, PNG. Maximum file size: 10 MB.</p>
                                            </div>
                                            <button type="submit" class="staff-button staff-button-primary justify-center">
                                                <i class="fa-solid fa-upload"></i>
                                                Replace Source File
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="source-file-missing-box">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <div>
                                        This package is encoded as metadata, but it is not yet supported by a source scan. Attach the scanned document, PDF, or image when available.
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('staff.source-record-packages.source-file.store', $package) }}" enctype="multipart/form-data" class="source-detail-form">
                                    @csrf
                                    <div class="source-file-upload-box">
                                        <label class="block text-xs font-black uppercase tracking-wide">Upload source file</label>
                                        <input type="file" name="source_file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                        <p class="mt-1 text-xs text-gray-500">Accepted files: PDF, JPG, JPEG, PNG. Maximum file size: 10 MB.</p>
                                    </div>
                                    <button type="submit" class="staff-button staff-button-primary justify-center">
                                        <i class="fa-solid fa-upload"></i>
                                        Attach Source File
                                    </button>
                                </form>
                            @endif
                        </div>                    </div>
                </section>

                <section class="source-detail-panel">
                    <div class="source-detail-panel-header">
                        <div>
                            <h3 class="source-detail-title">Linked Parcel</h3>
                            <p class="source-detail-help">Current main Parcel Record connection.</p>
                        </div>
                    </div>
                    <div class="source-detail-body">
                        @if ($package->parcel)
                            <a href="{{ route('staff.records.parcels.show', $package->parcel) }}" class="block rounded-xl border border-green-200 bg-green-50 p-4 text-green-900 hover:bg-green-100">
                                <p class="source-detail-label">Parcel Record</p>
                                <p class="mt-1 text-lg font-black">{{ $package->parcel->parcel_code }}</p>
                                <p class="mt-1 text-sm text-green-800">{{ $package->parcel->barangay ?? 'N/A' }}, {{ $package->parcel->municipality ?? 'N/A' }}</p>
                            </a>
                        @else
                            <div class="source-empty-state">This package is not yet linked to a main parcel record.</div>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-staff-shell>
