<x-staff-shell
    title="Add Parcel Record"
    subtitle="Encode a main parcel record for search, review, monitoring, and map visualization."
    active="parcel-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Parcel Records
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .parcel-create-layout {
                display: grid;
                grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.55fr);
                gap: 18px;
                align-items: start;
            }

            .parcel-create-main,
            .parcel-create-aside {
                display: grid;
                gap: 16px;
                min-width: 0;
            }

            .parcel-create-aside {
                position: sticky;
                top: 96px;
            }

            .parcel-create-card {
                border: 1px solid #dbe3ea;
                background: #ffffff;
                border-radius: 18px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }

            .parcel-create-card-header {
                padding: 18px 20px 14px;
                border-bottom: 1px solid #e5e7eb;
            }

            .parcel-create-title {
                margin: 0;
                font-size: 16px;
                line-height: 1.25;
                font-weight: 900;
                color: #0f172a;
            }

            .parcel-create-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                line-height: 1.45;
                color: #64748b;
            }

            .parcel-create-body {
                padding: 18px 20px 20px;
            }

            .parcel-create-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .parcel-create-grid.is-location {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .parcel-create-field label {
                display: block;
                margin-bottom: 6px;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.09em;
                text-transform: uppercase;
                color: #334155;
            }

            .parcel-create-input {
                width: 100%;
                border-radius: 12px;
                border-color: #cbd5e1;
                font-size: 14px;
                color: #0f172a;
                box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
            }

            .parcel-create-input:focus {
                border-color: #15803d;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
                --tw-ring-color: transparent;
            }

            .parcel-create-input[type="file"] {
                padding: 7px;
                background: #ffffff;
                cursor: pointer;
            }

            .parcel-create-input[type="file"]::file-selector-button {
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

            .parcel-create-input[type="file"]::file-selector-button:hover {
                background: #14532d;
            }

            .parcel-create-error {
                margin-top: 6px;
                font-size: 12px;
                font-weight: 700;
                color: #dc2626;
            }

            .parcel-create-note,
            .parcel-create-save-card {
                border: 1px solid #dbe3ea;
                background: #ffffff;
                border-radius: 18px;
                padding: 18px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .parcel-create-save-actions {
                display: grid;
                gap: 10px;
                margin-top: 14px;
            }

            .parcel-create-note {
                background: #f8fafc;
                font-size: 12.5px;
                line-height: 1.5;
                color: #475569;
            }

            .parcel-create-helper {
                margin-top: 6px;
                font-size: 12px;
                line-height: 1.45;
                color: #64748b;
            }

            @media (max-width: 1120px) {
                .parcel-create-layout {
                    grid-template-columns: 1fr;
                }

                .parcel-create-aside {
                    position: static;
                    grid-row: 1;
                }
            }

            @media (max-width: 760px) {
                .parcel-create-grid,
                .parcel-create-grid.is-location {
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

        $titleTypes = $titleTypes ?? \App\Models\Parcel::titleTypeOptions();
        $rodOffices = $rodOffices ?? \App\Models\Parcel::rodOfficeOptions();
    @endphp

    <form method="POST" enctype="multipart/form-data" action="{{ route('staff.records.parcels.store') }}" class="parcel-create-layout">
        @csrf

        <main class="parcel-create-main">
            <section class="parcel-create-card">
                <div class="parcel-create-card-header">
                    <h2 class="parcel-create-title">Parcel Information</h2>
                    <p class="parcel-create-subtitle">Encode the main agricultural parcel reference details used by records, applications, and map visualization.</p>
                </div>

                <div class="parcel-create-body">
                    <div class="parcel-create-grid">
                        <div class="parcel-create-field">
                            <label for="parcel_code">Parcel Code</label>
                            <input id="parcel_code" type="text" name="parcel_code" value="{{ old('parcel_code') }}" required class="parcel-create-input" placeholder="PARCEL-BANGA-001">
                            @error('parcel_code')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="status">Status</label>
                            <select id="status" name="status" required class="parcel-create-input">
                                @foreach ($parcelStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="title_no">Title Number</label>
                            <input id="title_no" type="text" name="title_no" value="{{ old('title_no') }}" class="parcel-create-input" placeholder="T-2026-0001">
                            @error('title_no')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="lot_number">Lot Number</label>
                            <input id="lot_number" type="text" name="lot_number" value="{{ old('lot_number') }}" class="parcel-create-input" placeholder="Lot 1234">
                            @error('lot_number')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="survey_plan_number">Survey Plan Number</label>
                            <input id="survey_plan_number" type="text" name="survey_plan_number" value="{{ old('survey_plan_number') }}" class="parcel-create-input" placeholder="PSD-07-000000">
                            @error('survey_plan_number')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="title_type">Title / Reference Type</label>
                            <select id="title_type" name="title_type" class="parcel-create-input">
                                <option value="">Select title/reference type</option>
                                @foreach ($titleTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('title_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('title_type')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="rod_office">Register of Deeds Office</label>
                            <select id="rod_office" name="rod_office" class="parcel-create-input">
                                <option value="">Select ROD office</option>
                                @foreach ($rodOffices as $value => $label)
                                    <option value="{{ $value }}" @selected(old('rod_office') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('rod_office')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="tax_decl_no">Tax Declaration Number</label>
                            <input id="tax_decl_no" type="text" name="tax_decl_no" value="{{ old('tax_decl_no') }}" class="parcel-create-input" placeholder="TD-2026-0001">
                            @error('tax_decl_no')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="area_square_meters">Total Area / Square Meters</label>
                            <input id="area_square_meters" type="number" step="0.01" min="0" name="area_square_meters" value="{{ old('area_square_meters') }}" class="parcel-create-input" placeholder="24000.00">
                            <p class="parcel-create-helper">Land registration commonly records area in square meters. Hectares may be computed for 5-hectare monitoring.</p>
                            @error('area_square_meters')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="area_hectares">Area / Hectares</label>
                            <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="parcel-create-input" placeholder="2.4000">
                            <p class="parcel-create-helper">Optional. Leave blank if square meters are encoded; the system computes it.</p>
                            @error('area_hectares')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="parcel-create-card">
                <div class="parcel-create-card-header">
                    <h2 class="parcel-create-title">Location</h2>
                    <p class="parcel-create-subtitle">Administrative location details for searching, reporting, and map reference.</p>
                </div>

                <div class="parcel-create-body">
                    <div class="parcel-create-grid is-location">
                        <div class="parcel-create-field">
                            <label for="province">Province</label>
                            <input id="province" type="text" name="province" value="{{ old('province', 'Negros Oriental') }}" class="parcel-create-input">
                            @error('province')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="municipality">Municipality</label>
                            <input id="municipality" type="text" name="municipality" value="{{ old('municipality') }}" class="parcel-create-input" placeholder="Bayawan City">
                            @error('municipality')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="parcel-create-field">
                            <label for="barangay">Barangay</label>
                            <input id="barangay" type="text" name="barangay" value="{{ old('barangay') }}" class="parcel-create-input" placeholder="Banga">
                            @error('barangay')<p class="parcel-create-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="parcel-create-card">
                <div class="parcel-create-card-header">
                    <h2 class="parcel-create-title">Reference File and Map Geometry</h2>
                    <p class="parcel-create-subtitle">Attach an optional reference scan and use the builder to generate valid GeoJSON without manually typing the Polygon structure.</p>
                </div>

                <div class="parcel-create-body" style="display:grid; gap:16px;">
                    <div class="parcel-create-field">
                        <label for="reference_photo">Reference Photo / Scan</label>
                        <input id="reference_photo" type="file" name="reference_photo" accept="image/*" class="parcel-create-input">
                        <p class="parcel-create-helper">Optional photo/scan of the title, tax declaration, sketch, or reference sheet used for encoding.</p>
                    </div>

                    <div class="parcel-create-field">
                        <label for="geometry_geojson">GeoJSON Geometry</label>
                        @include('staff.partials.geojson-polygon-editor', [
                            'fieldName' => 'geometry_geojson',
                            'fieldId' => 'geometry_geojson',
                            'value' => old('geometry_geojson'),
                            'inputClass' => 'parcel-create-input font-mono text-xs',
                            'errorClass' => 'parcel-create-error',
                            'rows' => 8,
                        ])
                    </div>
                </div>
            </section>

            <section class="parcel-create-card">
                <div class="parcel-create-card-header">
                    <h2 class="parcel-create-title">Remarks</h2>
                    <p class="parcel-create-subtitle">Optional staff notes for administrative reference.</p>
                </div>

                <div class="parcel-create-body">
                    <textarea name="remarks" rows="4" class="parcel-create-input">{{ old('remarks') }}</textarea>
                    @error('remarks')<p class="parcel-create-error">{{ $message }}</p>@enderror
                </div>
            </section>
        </main>

        <aside class="parcel-create-aside">
            <section class="parcel-create-save-card">
                <h3 class="parcel-create-title">Save Parcel Record</h3>
                <p class="parcel-create-subtitle">This creates a main parcel record that can appear in the parcel map if valid GeoJSON is provided.</p>

                <div class="parcel-create-save-actions">
                    <button type="submit" class="staff-button staff-button-primary justify-center">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Parcel Record
                    </button>
                    <a href="{{ route('staff.records.parcels.index') }}" class="staff-button staff-button-light justify-center">Cancel</a>
                </div>
            </section>

            <div class="parcel-create-note">
                Parcel encoding is limited to agricultural land records used for DAR clearance review, monitoring, and map visualization. It does not transfer ownership, prove final legal ownership, or mutate Registry of Deeds records.
            </div>
        </aside>
    </form>
</x-staff-shell>