<x-staff-shell
    title="Source Record Details"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Archive
        </a>
    </x-slot>

    @php
        $sourceLandownerName = $record->landowner_name ?: ($record->transferor_name ?: $record->transferee_name);
        $nameParts = $sourceLandownerName ? preg_split('/\s+/', trim($sourceLandownerName)) : [];
        $suggestedFirstName = $nameParts[0] ?? '';
        $suggestedLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        $geometryValue = $record->source_geometry_geojson;
        $geometryDisplay = is_array($geometryValue) ? json_encode($geometryValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $geometryValue;

        $sourceSummaryItems = [
            ['label' => 'Parcel Reference Code', 'value' => $record->parcel_code ?? '—'],
            ['label' => 'Title Number', 'value' => $record->title_number ?? '—'],
            ['label' => 'Landholding Reference', 'value' => $record->landholding_reference_number ?? '—'],
            ['label' => 'Lot / Survey', 'value' => trim(($record->lot_number ?? '—') . ' / ' . ($record->survey_number ?? '—'))],
            ['label' => 'Landowner / Owner Name', 'value' => $record->landowner_name ?? '—'],
            ['label' => 'Transfer Parties', 'value' => 'Transferor: ' . ($record->transferor_name ?? '—') . ' | Transferee: ' . ($record->transferee_name ?? '—')],
            ['label' => 'Area / Land Use Reference Notation', 'value' => ($record->area_hectares ? $record->area_hectares . ' ha' : '—') . ' | ' . ($record->crop_or_land_use ?? 'No land use reference notation recorded')],
            ['label' => 'Location', 'value' => ($record->municipality ?? '—') . ', ' . ($record->barangay ?? '—') . ', ' . ($record->province ?? 'Negros Oriental')],
            ['label' => 'Clearance / Application Ref.', 'value' => ($record->control_number ?? '—') . ' | Application: ' . ($record->application_reference_number ?? '—')],
            ['label' => 'Record Date / Status', 'value' => ($record->record_date?->format('M d, Y') ?? '—') . ' | ' . ($record->decision_status ?? '—')],
        ];
    @endphp

    <style>
        .source-detail-page {
            display: grid;
            gap: 1.25rem;
        }

        .source-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1.25rem;
            align-items: center;
        }

        .source-hero-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .source-badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.45rem 0.75rem;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .source-badge-soft.green {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .source-badge-soft.blue {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .source-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .source-info-cell {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 0.8rem;
            padding: 0.9rem 1rem;
            min-height: 4.7rem;
        }

        .source-info-label {
            margin: 0;
            font-size: 0.68rem;
            line-height: 1.2;
            font-weight: 900;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: #64748b;
        }

        .source-info-value {
            margin: 0.45rem 0 0;
            color: #0f172a;
            font-size: 0.92rem;
            line-height: 1.45;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .source-subgrid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
            gap: 1rem;
            align-items: start;
        }

        .source-side-card,
        .source-action-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 1rem;
            padding: 1rem;
        }

        .source-action-card.muted {
            background: #f8fafc;
        }

        .source-current-link {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
        }

        .source-linkage-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
            gap: 1rem;
            align-items: start;
        }

        .source-form-stack {
            display: grid;
            gap: 0.85rem;
        }

        .source-detail-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.65rem;
            flex-wrap: wrap;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 1rem;
        }

        .source-details-toggle {
            border: 1px solid #dbe4dd;
            background: #f8fafc;
            border-radius: 0.85rem;
            padding: 0.9rem 1rem;
        }

        .source-details-toggle > summary {
            cursor: pointer;
            color: #065f46;
            font-weight: 900;
        }

        .source-geometry-pre {
            margin-top: 0.75rem;
            max-height: 18rem;
            overflow: auto;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            border-radius: 0.8rem;
            background: #0f172a;
            color: #e5e7eb;
            padding: 1rem;
            font-size: 0.72rem;
            line-height: 1.55;
        }

        .source-mini-list {
            display: grid;
            gap: 0.75rem;
        }

        .source-mini-list dl {
            display: grid;
            gap: 0.65rem;
            margin: 0;
        }

        .source-mini-list dt {
            font-size: 0.67rem;
            font-weight: 900;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: #64748b;
        }

        .source-mini-list dd {
            margin: 0.25rem 0 0;
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        @media (max-width: 1100px) {
            .source-hero,
            .source-subgrid,
            .source-linkage-grid {
                grid-template-columns: 1fr;
            }

            .source-hero-badges {
                justify-content: flex-start;
            }
        }

        @media (max-width: 760px) {
            .source-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="source-detail-page">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="mb-1 font-black">Please fix the following:</div>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200 source-hero">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">
                        {{ $record->record_type_label }} · {{ $record->origin_label }}
                    </p>
                    <h2 class="mt-1 text-2xl font-black text-gray-950">Source Record #{{ $record->id }}</h2>
                    <p class="mt-2 max-w-4xl text-sm leading-relaxed text-gray-600">
                        Indexed source information used for clearance review, parcel reference checking, and traceability.
                    </p>
                </div>

                <div class="source-hero-badges">
                    <span class="source-badge-soft blue">
                        <i class="fa-solid fa-bookmark"></i>
                        {{ $record->source_record_scope_label }}
                    </span>
                    @if ($record->parcel)
                        <span class="source-badge-soft green">
                            <i class="fa-solid fa-link"></i>
                            Parcel Linked
                        </span>
                    @else
                        <span class="source-badge-soft">
                            <i class="fa-solid fa-link-slash"></i>
                            No Parcel Link
                        </span>
                    @endif
                    @if ($record->landowner)
                        <span class="source-badge-soft green">
                            <i class="fa-solid fa-user-check"></i>
                            Landowner Linked
                        </span>
                    @endif
                </div>
            </div>

            <div class="staff-panel-pad source-subgrid">
                <div>
                    <h3 class="staff-panel-title">Encoded Source Information</h3>
                    <p class="staff-panel-subtitle mt-1">Key values indexed from the source document.</p>

                    <div class="source-summary-grid mt-4">
                        @foreach ($sourceSummaryItems as $item)
                            <div class="source-info-cell">
                                <p class="source-info-label">{{ $item['label'] }}</p>
                                <p class="source-info-value">{{ $item['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($record->remarks || $record->boundary_description || $record->source_notes || $geometryDisplay)
                        <div class="mt-4 grid gap-3">
                            @if ($record->remarks)
                                <div class="source-info-cell bg-white">
                                    <p class="source-info-label">Remarks</p>
                                    <p class="mt-2 text-sm leading-relaxed text-gray-700">{{ $record->remarks }}</p>
                                </div>
                            @endif

                            @if ($record->boundary_description)
                                <div class="source-info-cell bg-white">
                                    <p class="source-info-label">Boundary / Technical Description</p>
                                    <p class="mt-2 text-sm leading-relaxed text-gray-700">{{ $record->boundary_description }}</p>
                                </div>
                            @endif

                            @if ($record->source_notes)
                                <div class="source-info-cell bg-white">
                                    <p class="source-info-label">Source Notes</p>
                                    <p class="mt-2 text-sm leading-relaxed text-gray-700">{{ $record->source_notes }}</p>
                                </div>
                            @endif

                            @if ($geometryDisplay)
                                <details class="source-details-toggle">
                                    <summary>
                                        <i class="fa-solid fa-draw-polygon mr-1"></i>
                                        View source geometry reference
                                    </summary>
                                    <pre class="source-geometry-pre">{{ $geometryDisplay }}</pre>
                                </details>
                            @endif
                        </div>
                    @endif
                </div>

                <aside class="source-mini-list">
                    <div class="source-side-card">
                        <h3 class="staff-panel-title">Source / Provenance</h3>
                        <dl class="mt-4">
                            <div>
                                <dt>Source Book / File</dt>
                                <dd>{{ $record->source_book ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>Page Number</dt>
                                <dd>{{ $record->page_number ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>Transcribed By</dt>
                                <dd>{{ $record->transcribed_by ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>Transcription Date</dt>
                                <dd>{{ $record->transcription_date?->format('M d, Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="source-side-card">
                        <h3 class="staff-panel-title">Source Package</h3>
                        @if ($record->package)
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">
                                This source record was generated from a digitized source package.
                            </p>
                            <a href="{{ route('staff.source-record-packages.show', $record->package) }}" class="mt-4 flex items-center justify-between rounded-xl border border-green-200 bg-green-50 p-4 text-green-900 hover:bg-green-100">
                                <span class="font-black">{{ $record->package->package_code }}</span>
                                <i class="fa-solid fa-box-open"></i>
                            </a>
                        @else
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">No source package is attached to this individual record.</p>
                        @endif
                    </div>

                    <div class="source-side-card">
                        <h3 class="staff-panel-title">Linked Parcel</h3>
                        @if ($record->parcel)
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">
                                Attached to a main parcel record for reference review only.
                            </p>
                            <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="mt-4 flex items-center justify-between rounded-xl border border-green-200 bg-green-50 p-4 text-green-900 hover:bg-green-100">
                                <span class="font-black">{{ $record->parcel->parcel_code }}</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        @else
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">No main parcel record is linked yet.</p>
                        @endif
                    </div>
                </aside>
            </div>
        </section>

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200 source-hero">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Landowner Record Linkage</p>
                    <h2 class="staff-panel-title mt-1">Source Party to Main Landowner Record</h2>
                    <p class="staff-panel-subtitle mt-1 max-w-4xl">
                        Use this only when staff confirms that the source party should be represented in the main Landowner Records module. This is an administrative link only.
                    </p>
                </div>

                @if ($record->landowner)
                    <span class="source-badge-soft green">
                        <i class="fa-solid fa-user-check"></i>
                        Linked
                    </span>
                @else
                    <span class="source-badge-soft">
                        <i class="fa-solid fa-user-clock"></i>
                        Not Linked
                    </span>
                @endif
            </div>

            <div class="staff-panel-pad source-linkage-grid">
                <div class="source-action-card muted">
                    <h3 class="font-black text-gray-950">Source Name Reference</h3>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="source-info-label">Encoded Owner Name</dt>
                            <dd class="mt-1 font-black text-gray-950">{{ $record->landowner_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="source-info-label">Transferor</dt>
                            <dd class="mt-1 font-black text-gray-950">{{ $record->transferor_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="source-info-label">Transferee</dt>
                            <dd class="mt-1 font-black text-gray-950">{{ $record->transferee_name ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if ($record->landowner)
                        <div class="source-current-link mt-4">
                            <p class="source-info-label">Currently Linked To</p>
                            <p class="mt-1 font-black text-green-900">{{ $record->landowner->full_name }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ $record->landowner->barangay ?? 'N/A' }}, {{ $record->landowner->municipality ?? 'N/A' }}</p>
                        </div>
                    @endif
                </div>

                <div class="grid gap-4">
                    <div class="source-action-card">
                        <h3 class="font-black text-gray-950">{{ $record->landowner ? 'Change Linked Landowner' : 'Link Existing Landowner' }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-gray-600">
                            Use this when the correct person already exists in Landowner Records.
                        </p>
                        <form method="POST" action="{{ route('staff.legacy-records.link-landowner', $record) }}" class="source-form-stack mt-4">
                            @csrf
                            <div>
                                <label class="staff-form-label">EXISTING LANDOWNER RECORD</label>
                                <select name="landowner_id" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    <option value="">Select landowner</option>
                                    @foreach ($landowners as $landowner)
                                        <option value="{{ $landowner->id }}" @selected($record->landowner_id === $landowner->id)>
                                            {{ $landowner->full_name }}
                                            @if ($landowner->barangay || $landowner->municipality)
                                                — {{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="staff-button staff-button-primary justify-center">
                                <i class="fa-solid fa-link"></i>
                                {{ $record->landowner ? 'Update Landowner Link' : 'Link Existing Landowner' }}
                            </button>
                        </form>
                    </div>

                    @unless ($record->landowner)
                        <div class="source-action-card">
                            <h3 class="font-black text-gray-950">Create Landowner From Source</h3>
                            <p class="mt-1 text-sm leading-relaxed text-gray-600">
                                Use this only if no matching Landowner Record exists yet. Staff must review the source details first.
                            </p>
                            <details class="source-details-toggle mt-4">
                                <summary>
                                    <i class="fa-solid fa-user-plus mr-1"></i>
                                    Open creation form
                                </summary>
                                <form method="POST" action="{{ route('staff.legacy-records.create-landowner', $record) }}" class="mt-4 source-form-stack">
                                    @csrf
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="staff-form-label">FIRST NAME *</label>
                                            <input name="first_name" required value="{{ old('first_name', $suggestedFirstName) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label class="staff-form-label">LAST NAME *</label>
                                            <input name="last_name" required value="{{ old('last_name', $suggestedLastName) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label class="staff-form-label">MIDDLE NAME</label>
                                            <input name="middle_name" value="{{ old('middle_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label class="staff-form-label">SUFFIX</label>
                                            <input name="suffix" value="{{ old('suffix') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="staff-form-label">ADDRESS LINE</label>
                                        <input name="address_line" value="{{ old('address_line') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                        <div>
                                            <label class="staff-form-label">BARANGAY</label>
                                            <input name="barangay" value="{{ old('barangay', $record->barangay) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label class="staff-form-label">MUNICIPALITY</label>
                                            <input name="municipality" value="{{ old('municipality', $record->municipality) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label class="staff-form-label">PROVINCE</label>
                                            <input name="province" value="{{ old('province', $record->province ?? 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="staff-form-label">CONTACT NUMBER</label>
                                        <input name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-xs leading-relaxed text-yellow-900">
                                        This creates a main Landowner Record for administrative use only. It does not certify legal ownership, execute transfer, or alter registry records.
                                    </div>
                                    <button type="submit" class="staff-button staff-button-dark justify-center">
                                        <i class="fa-solid fa-user-plus"></i>
                                        Create and Link Landowner
                                    </button>
                                </form>
                            </details>
                        </div>
                    @endunless
                </div>
            </div>
        </section>

        @if (! $record->parcel)
            <section class="staff-panel overflow-hidden">
                <div class="staff-panel-pad border-b border-gray-200">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Parcel Record Linkage</p>
                    <h2 class="staff-panel-title mt-1">Attach Source to Main Parcel Record</h2>
                    <p class="staff-panel-subtitle mt-1">
                        Link an existing parcel or create a main parcel record only after staff confirms the source reference. This does not create or transfer ownership.
                    </p>
                </div>

                <div class="staff-panel-pad grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <div class="source-action-card">
                        <h3 class="font-black text-gray-950">Link Existing Parcel</h3>
                        <p class="mt-1 text-sm leading-relaxed text-gray-600">Use this if the parcel already exists in the main Parcel Records module.</p>
                        <form method="POST" action="{{ route('staff.legacy-records.link-parcel', $record) }}" class="source-form-stack mt-4">
                            @csrf
                            <div>
                                <label class="staff-form-label">EXISTING PARCEL</label>
                                <select name="parcel_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
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
                                <i class="fa-solid fa-link"></i>
                                Link Source Record
                            </button>
                        </form>
                    </div>

                    <div class="source-action-card">
                        <h3 class="font-black text-gray-950">Create Main Parcel Record From Source</h3>
                        <p class="mt-1 text-sm leading-relaxed text-gray-600">Use this only after staff confirms the source represents a parcel that should be added to main Parcel Records.</p>
                        <details class="source-details-toggle mt-4">
                            <summary>
                                <i class="fa-solid fa-map-location-dot mr-1"></i>
                                Open parcel creation form
                            </summary>
                            <form method="POST" action="{{ route('staff.legacy-records.create-parcel', $record) }}" class="mt-4 source-form-stack">
                                @csrf
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="staff-form-label">PARCEL CODE *</label>
                                        <input name="parcel_code" value="{{ old('parcel_code', $record->parcel_code) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div>
                                        <label class="staff-form-label">TITLE NUMBER</label>
                                        <input name="title_no" value="{{ old('title_no', $record->title_number) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div>
                                        <label class="staff-form-label">AREA</label>
                                        <input type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares', $record->area_hectares) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div>
                                        <label class="staff-form-label">STATUS *</label>
                                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="staff-form-label">BARANGAY</label>
                                        <input name="barangay" value="{{ old('barangay', $record->barangay) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div>
                                        <label class="staff-form-label">MUNICIPALITY</label>
                                        <input name="municipality" value="{{ old('municipality', $record->municipality) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="staff-form-label">LINK EXISTING LANDOWNER AS ACTIVE LANDHOLDING</label>
                                        <select name="landowner_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                            <option value="">Do not link landowner yet</option>
                                            @foreach ($landowners as $landowner)
                                                <option value="{{ $landowner->id }}">
                                                    {{ $landowner->full_name }}
                                                    @if ($landowner->barangay || $landowner->municipality) — {{ $landowner->barangay ?? 'N/A' }}, {{ $landowner->municipality ?? 'N/A' }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">The map gets owner names through active landholding records.</p>
                                    </div>
                                    <div>
                                        <label class="staff-form-label">DATE ACQUIRED</label>
                                        <input type="date" name="date_acquired" value="{{ old('date_acquired') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div>
                                        <label class="staff-form-label">PROVINCE</label>
                                        <input name="province" value="{{ old('province', $record->province ?? 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="staff-form-label">PARCEL GEOJSON GEOMETRY</label>
                                        <textarea name="geometry_geojson" rows="6" class="w-full rounded-lg border-gray-300 font-mono text-xs shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('geometry_geojson', $geometryDisplay ?? '') }}</textarea>
                                        <p class="mt-1 text-xs text-gray-500">Only main Parcel Records with saved geometry appear on the Parcel Map Viewer.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="staff-form-label">REMARKS</label>
                                        <textarea name="remarks" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('remarks', 'Created from source record #' . $record->id . '.') }}</textarea>
                                    </div>
                                </div>
                                <button type="submit" class="staff-button staff-button-dark justify-center">
                                    <i class="fa-solid fa-plus"></i>
                                    Create Parcel Record
                                </button>
                            </form>
                        </details>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-staff-shell>
