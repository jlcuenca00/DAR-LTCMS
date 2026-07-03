<x-staff-shell
    title="Edit Parcel Record"
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Parcel Details
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .parcel-edit-page {
                display: grid;
                gap: 18px;
            }

            .parcel-edit-layout {
                display: grid;
                grid-template-columns: minmax(0, 1.65fr) minmax(340px, 0.65fr);
                gap: 18px;
                align-items: start;
            }

            .parcel-edit-main,
            .parcel-edit-aside {
                display: grid;
                gap: 16px;
                min-width: 0;
            }

            .parcel-edit-aside {
                position: sticky;
                top: 96px;
            }

            .parcel-edit-card {
                border: 1px solid #dbe3ea;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }

            .parcel-edit-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                padding: 18px 20px 14px;
                border-bottom: 1px solid #e5e7eb;
            }

            .parcel-edit-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.25;
                font-weight: 900;
                color: #0f172a;
            }

            .parcel-edit-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                line-height: 1.45;
                color: #64748b;
            }

            .parcel-edit-body {
                padding: 18px 20px 20px;
            }

            .parcel-edit-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .parcel-edit-grid.is-location {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .parcel-edit-field label {
                display: block;
                margin-bottom: 6px;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.09em;
                text-transform: uppercase;
                color: #334155;
            }

            .parcel-edit-input {
                width: 100%;
                border-radius: 12px;
                border-color: #cbd5e1;
                font-size: 14px;
                color: #0f172a;
                box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
            }

            .parcel-edit-input:focus {
                border-color: #15803d;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
                --tw-ring-color: transparent;
            }

            .parcel-edit-input[type="file"] {
                padding: 7px;
                background: #ffffff;
                cursor: pointer;
            }

            .parcel-edit-input[type="file"]::file-selector-button {
                margin-right: 12px;
                border: 1px solid #166534;
                border-radius: 9px;
                background: #166534;
                color: #ffffff;
                padding: 7px 12px;
                font-size: 12px;
                font-weight: 900;
                cursor: pointer;
            }

            .parcel-edit-input[type="file"]::file-selector-button:hover {
                background: #14532d;
            }

            .parcel-edit-helper {
                margin-top: 6px;
                font-size: 12px;
                line-height: 1.45;
                color: #64748b;
            }

            .parcel-edit-error {
                margin-top: 6px;
                font-size: 12px;
                font-weight: 700;
                color: #dc2626;
            }

            .parcel-edit-summary-card {
                border: 1px solid #bbf7d0;
                background: linear-gradient(180deg, #f7fef9 0%, #ffffff 100%);
                border-radius: 18px;
                padding: 18px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            }

            .parcel-edit-summary-top {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                gap: 12px;
                align-items: center;
            }

            .parcel-edit-avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border-radius: 14px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #047857;
                font-weight: 950;
            }

            .parcel-edit-code {
                margin: 0;
                font-size: 20px;
                line-height: 1.1;
                font-weight: 950;
                letter-spacing: 0.03em;
                color: #0f172a;
                word-break: break-word;
            }

            .parcel-edit-meta {
                margin: 4px 0 0;
                color: #64748b;
                font-size: 13px;
                line-height: 1.35;
            }

            .parcel-edit-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 14px;
            }

            .parcel-save-card {
                border: 1px solid #dbe3ea;
                background: #ffffff;
                border-radius: 18px;
                padding: 18px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .parcel-save-actions {
                display: grid;
                gap: 10px;
                margin-top: 14px;
            }

            .parcel-edit-note {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 14px;
                padding: 13px 14px;
                font-size: 12.5px;
                line-height: 1.5;
                color: #475569;
            }

            @media (max-width: 1180px) {
                .parcel-edit-layout {
                    grid-template-columns: 1fr;
                }

                .parcel-edit-aside {
                    position: static;
                    grid-row: 1;
                }
            }

            @media (max-width: 760px) {
                .parcel-edit-card-header,
                .parcel-edit-body,
                .parcel-save-card,
                .parcel-edit-summary-card {
                    padding: 16px;
                }

                .parcel-edit-grid,
                .parcel-edit-grid.is-location {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </x-slot>

    @php
        $parcelStatuses = $parcelStatuses ?? [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'linked_application' => 'Linked to Application',
            'flagged' => 'Flagged for Review',
        ];

        $currentStatusLabel = $parcel->status ? ucwords(str_replace('_', ' ', $parcel->status)) : 'Status N/A';
        $titleTypes = $titleTypes ?? \App\Models\Parcel::titleTypeOptions();
        $rodOffices = $rodOffices ?? \App\Models\Parcel::rodOfficeOptions();
    @endphp

    <div class="parcel-edit-page">

        <form method="POST" enctype="multipart/form-data" action="{{ route('staff.records.parcels.update', $parcel) }}" class="parcel-edit-layout">
            @csrf
            @method('PATCH')

            <main class="parcel-edit-main">
                <section class="parcel-edit-card">
                    <div class="parcel-edit-card-header">
                        <div>
                            <h2 class="parcel-edit-title">Parcel Information</h2>
                            <p class="parcel-edit-subtitle">Core reference values used across parcel records, maps, and application review screens.</p>
                        </div>
                        <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">{{ $currentStatusLabel }}</span>
                    </div>

                    <div class="parcel-edit-body">
                        <div class="parcel-edit-grid">
                            <div class="parcel-edit-field">
                                <label for="parcel_code">Parcel Code</label>
                                <input id="parcel_code" type="text" name="parcel_code" value="{{ old('parcel_code', $parcel->parcel_code) }}" required class="parcel-edit-input">
                                @error('parcel_code')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="status">Status</label>
                                <select id="status" name="status" required class="parcel-edit-input">
                                    @foreach ($parcelStatuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $parcel->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="title_no">Title Number</label>
                                <input id="title_no" type="text" name="title_no" value="{{ old('title_no', $parcel->title_no) }}" class="parcel-edit-input">
                                @error('title_no')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="lot_number">Lot Number</label>
                                <input id="lot_number" type="text" name="lot_number" value="{{ old('lot_number', $parcel->lot_number) }}" class="parcel-edit-input">
                                @error('lot_number')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="survey_plan_number">Survey Plan Number</label>
                                <input id="survey_plan_number" type="text" name="survey_plan_number" value="{{ old('survey_plan_number', $parcel->survey_plan_number) }}" class="parcel-edit-input">
                                @error('survey_plan_number')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="title_type">Title / Reference Type</label>
                                <select id="title_type" name="title_type" class="parcel-edit-input">
                                    <option value="">Select title/reference type</option>
                                    @foreach ($titleTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('title_type', $parcel->title_type) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('title_type')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="rod_office">Register of Deeds Office</label>
                                <select id="rod_office" name="rod_office" class="parcel-edit-input">
                                    <option value="">Select ROD office</option>
                                    @foreach ($rodOffices as $value => $label)
                                        <option value="{{ $value }}" @selected(old('rod_office', $parcel->rod_office) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('rod_office')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="tax_decl_no">Tax Declaration Number</label>
                                <input id="tax_decl_no" type="text" name="tax_decl_no" value="{{ old('tax_decl_no', $parcel->tax_decl_no) }}" class="parcel-edit-input">
                                @error('tax_decl_no')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="area_square_meters">Total Area / Square Meters</label>
                                <input id="area_square_meters" type="number" step="0.01" min="0" name="area_square_meters" value="{{ old('area_square_meters', $parcel->area_square_meters) }}" class="parcel-edit-input">
                                <p class="parcel-edit-helper">Land registration commonly records area in square meters. Hectares may be computed for monitoring.</p>
                                @error('area_square_meters')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="area_hectares">Area / Hectares</label>
                                <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares', $parcel->area_hectares) }}" class="parcel-edit-input">
                                <p class="parcel-edit-helper">Optional reference. Leave blank if square meters are encoded; the system computes it.</p>
                                @error('area_hectares')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>
</div>
                    </div>
                </section>

                <section class="parcel-edit-card">
                    <div class="parcel-edit-card-header">
                        <div>
                            <h2 class="parcel-edit-title">Location</h2>
                            <p class="parcel-edit-subtitle">Administrative location details for search and map reference.</p>
                        </div>
                    </div>

                    <div class="parcel-edit-body">
                        <div class="parcel-edit-grid is-location">
                            <div class="parcel-edit-field">
                                <label for="province">Province</label>
                                <input id="province" type="text" name="province" value="{{ old('province', $parcel->province ?? 'Negros Oriental') }}" class="parcel-edit-input">
                                @error('province')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="municipality">Municipality</label>
                                <input id="municipality" type="text" name="municipality" value="{{ old('municipality', $parcel->municipality) }}" class="parcel-edit-input">
                                @error('municipality')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="parcel-edit-field">
                                <label for="barangay">Barangay</label>
                                <input id="barangay" type="text" name="barangay" value="{{ old('barangay', $parcel->barangay) }}" class="parcel-edit-input">
                                @error('barangay')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="parcel-edit-card">
                    <div class="parcel-edit-card-header">
                        <div>
                            <h2 class="parcel-edit-title">Map Geometry</h2>
                            <p class="parcel-edit-subtitle">Update the GeoJSON Polygon used by the parcel map. The builder prevents staff from typing the full structure manually.</p>
                        </div>
                    </div>

                    <div class="parcel-edit-body">
                        <div class="parcel-edit-field">
                            <label for="geometry_geojson">GeoJSON Geometry</label>
                            @include('staff.partials.geojson-polygon-editor', [
                                'fieldName' => 'geometry_geojson',
                                'fieldId' => 'geometry_geojson',
                                'value' => old('geometry_geojson', $parcel->geometry_geojson ? json_encode($parcel->geometry_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ''),
                                'inputClass' => 'parcel-edit-input font-mono text-xs',
                                'errorClass' => 'parcel-edit-error',
                                'rows' => 8,
                            ])
                        </div>
                    </div>
                </section>

                <section class="parcel-edit-card">
                    <div class="parcel-edit-card-header">
                        <div>
                            <h2 class="parcel-edit-title">Remarks</h2>
                            <p class="parcel-edit-subtitle">Optional staff notes for administrative reference.</p>
                        </div>
                    </div>

                    <div class="parcel-edit-body" style="display:grid; gap:14px;">
                        <div class="parcel-edit-field">
                            <label for="reference_photo">Reference Photo / Scan</label>
                            <input id="reference_photo" type="file" name="reference_photo" accept="image/*" class="parcel-edit-input">
                            <p class="parcel-edit-helper">Optional photo/scan of the title, tax declaration, sketch, or reference sheet used for encoding.</p>
                        </div>

                        <div class="parcel-edit-field">
                            <label for="remarks">Remarks</label>
                            <textarea id="remarks" name="remarks" rows="4" class="parcel-edit-input">{{ old('remarks', $parcel->remarks) }}</textarea>
                            @error('remarks')<p class="parcel-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
            </main>

            <aside class="parcel-edit-aside">
                <section class="parcel-edit-summary-card">
                    <div class="parcel-edit-summary-top">
                        <span class="parcel-edit-avatar">{{ \Illuminate\Support\Str::of($parcel->parcel_code)->substr(0, 1)->upper() }}</span>
                        <div>
                            <p class="parcel-eyebrow">Current Parcel Record</p>
                            <h3 class="parcel-edit-code">{{ $parcel->parcel_code }}</h3>
                            <p class="parcel-edit-meta">{{ $parcel->municipality ?? 'No municipality' }}{{ $parcel->barangay ? ', '.$parcel->barangay : '' }}</p>
                            <p class="parcel-edit-meta">{{ $parcel->title_no ?? 'No title number' }}{{ $parcel->lot_number ? ' · Lot '.$parcel->lot_number : '' }}</p>
                        </div>
                    </div>

                    <div class="parcel-edit-badges">
                        <span class="staff-badge {{ $parcel->status === 'active' ? 'staff-badge-green' : 'staff-badge-slate' }}">{{ $currentStatusLabel }}</span>
                    </div>
                </section>

                <section class="parcel-save-card">
                    <h3 class="parcel-edit-title">Save Changes</h3>
                    <p class="parcel-edit-subtitle">Updates are audit logged for administrative traceability and record integrity.</p>

                    <div class="parcel-save-actions">
                        <button type="submit" class="staff-button staff-button-primary justify-center">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Parcel Record
                        </button>
                        <a href="{{ route('staff.records.parcels.show', $parcel) }}" class="staff-button staff-button-light justify-center">
                            Cancel
                        </a>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</x-staff-shell>
