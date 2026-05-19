<x-staff-shell
    title="Landowner Details"
    active="landowner-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.landowners.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Landowners
        </a>
    </x-slot>

    <style>
        .landowner-page {
            display: grid;
            gap: 1.25rem;
        }

        .landowner-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(360px, 0.8fr);
            gap: 1.25rem;
            align-items: stretch;
        }

        .landowner-profile-main {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .landowner-profile-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
        }

        .landowner-profile-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.65rem;
            flex: 0 0 auto;
        }

        .landowner-profile-edit {
            min-height: 2.35rem;
            padding-inline: 0.95rem;
            white-space: nowrap;
        }

        .landowner-eyebrow {
            margin: 0;
            color: #166534;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .landowner-name {
            margin: 0.45rem 0 0;
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1.15;
        }

        .landowner-record-id {
            margin: 0.4rem 0 0;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .landowner-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .landowner-info-card {
            min-height: 5.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            background: #f8fafc;
            padding: 1rem;
        }

        .landowner-info-label,
        .landholding-field-label {
            display: block;
            margin: 0 0 0.45rem;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.11em;
            text-transform: uppercase;
        }

        .landowner-info-value {
            margin: 0;
            color: #111827;
            font-size: 0.98rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .landowner-info-subvalue {
            margin: 0.2rem 0 0;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .hectare-panel {
            display: flex;
            flex-direction: column;
            gap: 0.95rem;
        }

        .hectare-focus-card {
            border: 1px solid #bbf7d0;
            border-radius: 1rem;
            background: #f0fdf4;
            padding: 1.25rem;
        }

        .hectare-label {
            margin: 0;
            color: #166534;
            font-size: 0.82rem;
            font-weight: 900;
        }

        .hectare-value {
            margin: 0.55rem 0 0;
            color: #052e16;
            font-size: 2.25rem;
            font-weight: 900;
            line-height: 1;
        }

        .hectare-metric-list {
            display: grid;
            gap: 0.65rem;
        }

        .hectare-metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 0.75rem 0.9rem;
        }

        .hectare-metric-row span {
            color: #475569;
            font-size: 0.84rem;
            font-weight: 800;
        }

        .hectare-metric-row strong {
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .landholding-add-card {
            border: 1px solid #dbe4dd;
            border-radius: 1rem;
            background: #ffffff;
            overflow: hidden;
        }

        .landholding-add-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            padding: 1.15rem 1.25rem;
        }

        .landholding-add-title {
            margin: 0;
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 900;
        }

        .landholding-add-copy {
            margin: 0.35rem 0 0;
            max-width: 58rem;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .landholding-form-body {
            padding: 1.25rem;
        }

        .landholding-form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 1rem;
        }

        .landholding-field {
            min-width: 0;
        }

        .landholding-field.span-2 { grid-column: span 2; }
        .landholding-field.span-3 { grid-column: span 3; }
        .landholding-field.span-4 { grid-column: span 4; }
        .landholding-field.span-5 { grid-column: span 5; }
        .landholding-field.span-6 { grid-column: span 6; }
        .landholding-field.span-7 { grid-column: span 7; }
        .landholding-field.span-8 { grid-column: span 8; }
        .landholding-field.span-12 { grid-column: 1 / -1; }

        .landholding-input {
            width: 100%;
            min-height: 2.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.65rem;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.9rem;
            padding: 0.55rem 0.75rem;
        }

        .landholding-input:focus {
            border-color: #15803d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.13);
        }

        .landholding-form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 1rem 1.25rem;
        }

        .landholding-footer-note {
            margin: 0;
            max-width: 50rem;
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.5;
        }

        .landholding-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 4rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .landholding-edit-box {
            min-width: 18rem;
            border: 1px solid #dbe4dd;
            border-radius: 0.75rem;
            background: #ffffff;
            padding: 0.75rem;
        }

        .landholding-edit-box summary {
            color: #166534;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 900;
        }

        .landholding-edit-form {
            display: grid;
            gap: 0.65rem;
            margin-top: 0.8rem;
        }

        .landholding-mini-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .landholding-mini-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.9rem;
            background: #f8fafc;
            padding: 0.95rem 1rem;
        }

        .landholding-mini-card.is-green {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .landholding-mini-label {
            margin: 0;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .landholding-mini-value {
            margin: 0.35rem 0 0;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 950;
            line-height: 1;
        }

        .landholding-mini-card.is-green .landholding-mini-value {
            color: #052e16;
        }

        .landholding-card-list {
            display: grid;
            gap: 0.9rem;
        }

        .landholding-record-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .landholding-record-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #edf2f7;
        }

        .landholding-record-title {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 950;
            line-height: 1.25;
        }

        .landholding-record-subtitle {
            margin: 0.2rem 0 0;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .landholding-record-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 0.9rem;
        }

        .landholding-record-field {
            min-width: 0;
            border: 1px solid #edf2f7;
            border-radius: 0.75rem;
            background: #f8fafc;
            padding: 0.75rem;
        }

        .landholding-record-field.is-wide {
            grid-column: span 2;
        }

        .landholding-record-label {
            margin: 0;
            color: #64748b;
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .landholding-record-value {
            margin: 0.25rem 0 0;
            color: #111827;
            font-size: 0.88rem;
            font-weight: 850;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .landholding-record-note {
            margin-top: 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.8rem;
            background: #f8fafc;
            padding: 0.75rem;
            color: #475569;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .landholding-record-actions {
            margin-top: 0.85rem;
            display: flex;
            justify-content: flex-end;
        }

        .landowner-related-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .related-card-list {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .related-item {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
            padding: 0.9rem 1rem;
            text-decoration: none;
            transition: 150ms ease;
        }

        .related-item:hover {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .related-item-title {
            margin: 0;
            color: #166534;
            font-weight: 900;
        }

        .related-item-meta {
            margin: 0.25rem 0 0;
            color: #64748b;
            font-size: 0.78rem;
        }

        @media (max-width: 1280px) {
            .landowner-summary-grid,
            .landowner-related-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .landowner-info-grid {
                grid-template-columns: 1fr;
            }

            .landholding-form-grid,
            .landholding-mini-summary,
            .landholding-record-grid {
                grid-template-columns: 1fr;
            }

            .landholding-record-field.is-wide {
                grid-column: 1 / -1;
            }

            .landholding-field,
            .landholding-field.span-2,
            .landholding-field.span-3,
            .landholding-field.span-4,
            .landholding-field.span-5,
            .landholding-field.span-6,
            .landholding-field.span-7,
            .landholding-field.span-8,
            .landholding-field.span-12 {
                grid-column: 1 / -1;
            }

            .landholding-add-header,
            .landholding-form-footer,
            .landowner-profile-head {
                flex-direction: column;
                align-items: stretch;
            }

            .landowner-profile-actions {
                align-items: stretch;
            }

            .landowner-profile-edit,
            .landholding-form-footer .staff-button {
                width: 100%;
            }
        }
    </style>

    <div class="landowner-page">

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

        @php
            $activeHoldings = $landowner->landholdings->where('status', 'active');
            $inactiveHoldings = $landowner->landholdings->where('status', '!=', 'active');
            $statusBadge = match ($hectareSummary['status']) {
                'over_limit' => 'staff-badge-red',
                'near_limit' => 'staff-badge-amber',
                default => 'staff-badge-green',
            };
        @endphp

        <span class="sr-only">Computed Hectares Only</span>

        <section class="landowner-summary-grid">
            <div class="staff-panel staff-panel-pad landowner-profile-main">
                <div class="landowner-profile-head">
                    <div>
                        <p class="landowner-eyebrow">Landowner / Person Record</p>
                        <h2 class="landowner-name">{{ $landowner->full_name }}</h2>
                        <p class="landowner-record-id">Record ID: {{ $landowner->id }}</p>
                    </div>

                    <div class="landowner-profile-actions">
                        <span class="staff-badge {{ $landowner->user ? 'staff-badge-green' : 'staff-badge-slate' }}">
                            {{ $landowner->user ? 'Linked User Account' : 'No User Account' }}
                        </span>

                        <a href="{{ route('staff.records.landowners.edit', $landowner) }}" class="staff-button staff-button-primary landowner-profile-edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit Landowner Record
                        </a>
                    </div>
                </div>

                <div class="landowner-info-grid">
                    <div class="landowner-info-card">
                        <p class="landowner-info-label">Contact</p>
                        <p class="landowner-info-value">{{ $landowner->contact_number ?? 'N/A' }}</p>
                    </div>

                    <div class="landowner-info-card">
                        <p class="landowner-info-label">Location</p>
                        <p class="landowner-info-value">{{ $landowner->municipality ?? 'N/A' }}</p>
                        <p class="landowner-info-subvalue">{{ $landowner->barangay ?? 'N/A' }}</p>
                    </div>

                    <div class="landowner-info-card">
                        <p class="landowner-info-label">Address</p>
                        <p class="landowner-info-value">{{ $landowner->address_line ?? 'N/A' }}</p>
                    </div>

                    <div class="landowner-info-card">
                        <p class="landowner-info-label">Linked Account</p>
                        @if ($landowner->user)
                            <p class="landowner-info-value">{{ $landowner->user->name }}</p>
                            <p class="landowner-info-subvalue">{{ $landowner->user->email }}</p>
                        @else
                            <p class="landowner-info-value text-gray-500">No landowner portal account is linked.</p>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="staff-panel staff-panel-pad hectare-panel">
                <div>
                    <p class="landowner-eyebrow">5-Hectare Reference Check</p>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500">
                        Assistive calculation based on encoded active landholding records.
                    </p>
                </div>

                <div class="hectare-focus-card">
                    <p class="hectare-label">Current Active Hectares</p>
                    <p class="hectare-value">{{ number_format($hectareSummary['current_active_total'], 4) }} ha</p>
                    <span class="staff-badge mt-4 {{ $statusBadge }}">{{ $hectareSummary['status_label'] }}</span>
                </div>

                <div class="hectare-metric-list">
                    <div class="hectare-metric-row">
                        <span>Reference limit</span>
                        <strong>{{ number_format($hectareSummary['limit'], 4) }} ha</strong>
                    </div>

                    <div class="hectare-metric-row">
                        <span>Remaining based on active records</span>
                        <strong>{{ number_format(max(0, $hectareSummary['limit'] - $hectareSummary['current_active_total']), 4) }} ha</strong>
                    </div>
                </div>

                <p class="m-0 text-xs leading-relaxed text-gray-500">Based on active landholding records encoded for this landowner.</p>
            </aside>
        </section>

        <section id="landholdings" class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="staff-panel-title">Landholding Records</h2>
                        <p class="staff-panel-subtitle">
                            Encode and review parcel-linked landholding records used for hectare monitoring, reference tracking, and staff review.
                        </p>
                    </div>
                    <span class="staff-badge staff-badge-green">{{ $activeHoldings->count() }} active</span>
                </div>

                <div class="landholding-mini-summary">
                    <div class="landholding-mini-card is-green">
                        <p class="landholding-mini-label">Active Area</p>
                        <p class="landholding-mini-value">{{ number_format($hectareSummary['current_active_total'], 4) }} ha</p>
                    </div>
                    <div class="landholding-mini-card">
                        <p class="landholding-mini-label">Active Records</p>
                        <p class="landholding-mini-value">{{ $activeHoldings->count() }}</p>
                    </div>
                    <div class="landholding-mini-card">
                        <p class="landholding-mini-label">Other Records</p>
                        <p class="landholding-mini-value">{{ $inactiveHoldings->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="staff-panel-pad border-b border-gray-200 bg-gray-50">
                <div class="landholding-add-card">
                    <div class="landholding-add-header">
                        <div>
                            <h3 class="landholding-add-title">Add Landholding Record</h3>
                            <p class="landholding-add-copy">
                                Add an encoded landholding reference for hectare monitoring. Active entries affect the computed current hectares shown for this landowner.
                            </p>
                        </div>
                        <span class="staff-badge staff-badge-slate">Records Management Only</span>
                    </div>

                    <form method="POST" action="{{ route('staff.records.landowners.landholdings.store', $landowner) }}" enctype="multipart/form-data" data-landholding-form>
                        @csrf

                        <div class="landholding-form-body">
                            <div class="landholding-form-grid">
                                <div class="landholding-field span-6">
                                    <label class="landholding-field-label">Parcel</label>
                                    <select name="parcel_id" class="landholding-input" required data-parcel-autofill>
                                        <option value="">Select parcel record</option>
                                        @foreach ($parcels as $parcel)
                                            @php($parcelReferenceText = collect([filled($parcel->title_no) ? 'Title: '.$parcel->title_no : null, filled($parcel->tax_decl_no) ? 'Tax Declaration: '.$parcel->tax_decl_no : null])->filter()->implode(' / '))
                                            <option value="{{ $parcel->id }}" data-area="{{ $parcel->area_hectares }}" data-reference="{{ $parcelReferenceText }}" @selected(old('parcel_id') == $parcel->id)>
                                                {{ $parcel->parcel_code }} @if($parcel->title_no) — {{ $parcel->title_no }} @endif @if($parcel->municipality) — {{ $parcel->municipality }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="landholding-field span-3">
                                    <label class="landholding-field-label">Area hectares</label>
                                    <input type="number" step="0.0001" min="0.0001" name="area_hectares" value="{{ old('area_hectares') }}" class="landholding-input" required data-area-field>
                                </div>

                                <div class="landholding-field span-3">
                                    <label class="landholding-field-label">Status</label>
                                    <select name="status" class="landholding-input" required>
                                        @foreach (\App\Models\Landholding::STATUSES as $status)
                                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="landholding-field span-3">
                                    <label class="landholding-field-label">Date acquired</label>
                                    <input type="date" name="date_acquired" value="{{ old('date_acquired') }}" class="landholding-input">
                                </div>

                                <div class="landholding-field span-3">
                                    <label class="landholding-field-label">Date transferred</label>
                                    <input type="date" name="date_transferred" value="{{ old('date_transferred') }}" class="landholding-input">
                                </div>

                                <div class="landholding-field span-6">
                                    <label class="landholding-field-label">Source/reference no.</label>
                                    <input type="text" name="source_reference_number" value="{{ old('source_reference_number') }}" class="landholding-input" placeholder="Title, tax declaration, clearance, or source reference" data-reference-field>
                                </div>

                                <div class="landholding-field span-6">
                                    <label class="landholding-field-label">Reference photo / scan</label>
                                    <input type="file" name="reference_photo" accept="image/*" class="landholding-input">
                                    <p class="mt-1 text-xs leading-relaxed text-gray-500">Optional photo or scan of the source page/card used as encoding basis.</p>
                                </div>

                                <div class="landholding-field span-12">
                                    <label class="landholding-field-label">Remarks</label>
                                    <textarea name="remarks" rows="3" class="landholding-input" placeholder="Optional staff notes for record basis and traceability">{{ old('remarks') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="landholding-form-footer">
                            <p class="landholding-footer-note">
                                Changes are stored as staff-encoded landholding records and are included in audit/log review workflows.
                            </p>
                            <button type="submit" class="staff-button staff-button-primary">
                                <i class="fa-solid fa-plus"></i>
                                Add Landholding
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="staff-panel-pad bg-white">
                @if ($landowner->landholdings->isEmpty())
                    <div class="landholding-empty">No landholding records encoded yet.</div>
                @else
                    <div class="landholding-card-list">
                        @foreach ($landowner->landholdings->sortByDesc('created_at') as $holding)
                            <article id="landholding-{{ $holding->id }}" class="landholding-record-card">
                                <div class="landholding-record-head">
                                    <div>
                                        <p class="landholding-record-title">{{ $holding->parcel?->parcel_code ?? 'Unlinked parcel' }}</p>
                                        <p class="landholding-record-subtitle">
                                            {{ $holding->parcel?->title_no ?? 'No title no.' }}
                                            @if ($holding->parcel?->municipality || $holding->parcel?->barangay)
                                                · {{ $holding->parcel?->municipality ?? 'N/A' }} / {{ $holding->parcel?->barangay ?? 'N/A' }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="staff-badge {{ $holding->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">
                                        {{ ucwords(str_replace('_', ' ', $holding->status)) }}
                                    </span>
                                </div>

                                <div class="landholding-record-grid">
                                    <div class="landholding-record-field">
                                        <p class="landholding-record-label">Area</p>
                                        <p class="landholding-record-value">{{ number_format((float) $holding->area_hectares, 4) }} ha</p>
                                    </div>
                                    <div class="landholding-record-field">
                                        <p class="landholding-record-label">Date Acquired</p>
                                        <p class="landholding-record-value">{{ $holding->date_acquired?->format('M d, Y') ?? 'N/A' }}</p>
                                    </div>
                                    <div class="landholding-record-field">
                                        <p class="landholding-record-label">Date Transferred</p>
                                        <p class="landholding-record-value">{{ $holding->date_transferred?->format('M d, Y') ?? 'N/A' }}</p>
                                    </div>
                                    <div class="landholding-record-field">
                                        <p class="landholding-record-label">Reference</p>
                                        <p class="landholding-record-value">
                                            {{ $holding->source_reference_number ?? 'N/A' }}
                                            @if ($holding->sourceApplication)
                                                <br><a href="{{ route('staff.applications.show', $holding->sourceApplication) }}" class="staff-link">{{ $holding->sourceApplication->application_code }}</a>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if (filled($holding->remarks))
                                    <div class="landholding-record-note">{{ $holding->remarks }}</div>
                                @endif

                                <div class="landholding-record-actions">
                                    <details class="landholding-edit-box">
                                        <summary>Edit landholding</summary>
                                        <form method="POST" action="{{ route('staff.records.landowners.landholdings.update', [$landowner, $holding]) }}" class="landholding-edit-form" enctype="multipart/form-data" data-landholding-form>
                                            @csrf
                                            @method('PATCH')

                                            <select name="parcel_id" class="landholding-input" required data-parcel-autofill>
                                                @foreach ($parcels as $parcel)
                                                    @php($parcelReferenceText = collect([filled($parcel->title_no) ? 'Title: '.$parcel->title_no : null, filled($parcel->tax_decl_no) ? 'Tax Declaration: '.$parcel->tax_decl_no : null])->filter()->implode(' / '))
                                                    <option value="{{ $parcel->id }}" data-area="{{ $parcel->area_hectares }}" data-reference="{{ $parcelReferenceText }}" @selected((int) $holding->parcel_id === (int) $parcel->id)>{{ $parcel->parcel_code }} @if($parcel->title_no) — {{ $parcel->title_no }} @endif</option>
                                                @endforeach
                                            </select>

                                            <input type="number" step="0.0001" min="0.0001" name="area_hectares" value="{{ $holding->area_hectares }}" class="landholding-input" required data-area-field>

                                            <select name="status" class="landholding-input" required>
                                                @foreach (\App\Models\Landholding::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected($holding->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                                @endforeach
                                            </select>

                                            <input type="date" name="date_acquired" value="{{ $holding->date_acquired?->format('Y-m-d') }}" class="landholding-input">
                                            <input type="date" name="date_transferred" value="{{ $holding->date_transferred?->format('Y-m-d') }}" class="landholding-input">
                                            <input type="text" name="source_reference_number" value="{{ $holding->source_reference_number }}" class="landholding-input" placeholder="Source/reference no." data-reference-field>
                                            <textarea name="remarks" rows="2" class="landholding-input" placeholder="Remarks">{{ $holding->remarks }}</textarea>

                                            <button type="submit" class="staff-button staff-button-primary">Save Landholding</button>
                                        </form>
                                    </details>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="landowner-related-grid">
            <div class="staff-panel staff-panel-pad">
                <h2 class="staff-panel-title">Related Applications</h2>
                <p class="staff-panel-subtitle">Applications where this record appears as transferor or transferee.</p>

                <div class="related-card-list">
                    @forelse ($landowner->transferorApplications->merge($landowner->transfereeApplications)->unique('id')->sortByDesc('created_at') as $application)
                        <a href="{{ route('staff.applications.show', $application) }}" class="related-item">
                            <p class="related-item-title">{{ $application->application_code }}</p>
                            <p class="related-item-meta">{{ ucwords(str_replace('_', ' ', $application->status)) }} · {{ $application->created_at?->format('M d, Y') }}</p>
                        </a>
                    @empty
                        <p class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">No related applications found.</p>
                    @endforelse
                </div>
            </div>

            <div class="staff-panel staff-panel-pad">
                <h2 class="staff-panel-title">Linked Source Records</h2>
                <p class="staff-panel-subtitle">Staff-confirmed source/provenance links for traceability and review.</p>

                <div class="related-card-list">
                    @foreach ($landowner->sourceRecordPackages as $package)
                        <a href="{{ route('staff.source-record-packages.show', $package) }}" class="related-item">
                            <p class="related-item-title">{{ $package->package_code }}</p>
                            <p class="related-item-meta">Source package</p>
                        </a>
                    @endforeach

                    @foreach ($landowner->sourceRecords as $record)
                        <a href="{{ route('staff.legacy-records.show', $record) }}" class="related-item">
                            <p class="related-item-title">{{ $record->title_number ?? $record->control_number ?? ('Source Record #' . $record->id) }}</p>
                            <p class="related-item-meta">{{ ucwords(str_replace('_', ' ', $record->record_type)) }}</p>
                        </a>
                    @endforeach

                    @if ($landowner->sourceRecordPackages->isEmpty() && $landowner->sourceRecords->isEmpty())
                        <p class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">No linked source records found.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>


    <script data-landholding-autofill-script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-landholding-form]').forEach(function (form) {
                const parcelSelect = form.querySelector('[data-parcel-autofill]');
                const areaField = form.querySelector('[data-area-field]');
                const referenceField = form.querySelector('[data-reference-field]');
                if (!parcelSelect) return;
                parcelSelect.addEventListener('change', function () {
                    const option = parcelSelect.options[parcelSelect.selectedIndex];
                    if (!option) return;
                    if (areaField && option.dataset.area && !areaField.value) areaField.value = parseFloat(option.dataset.area).toFixed(4);
                    if (referenceField && option.dataset.reference && !referenceField.value) referenceField.value = option.dataset.reference;
                });
            });
        });
    </script>

</x-staff-shell>
