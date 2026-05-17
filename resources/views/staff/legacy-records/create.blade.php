<x-staff-shell
    title="Encode Single Source Record"
    active="source-records"
>
    <x-slot name="actions">
        <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Archive
        </a>
        <a href="{{ route('staff.source-record-packages.create') }}" class="staff-button staff-button-primary">
            <i class="fa-solid fa-layer-group"></i>
            Encode Package Instead
        </a>
    </x-slot>


    <x-slot name="styles">
        <style>
            .source-submit-footer {
                border-top: 1px solid #e5e7eb;
                background: #f8fafc;
                padding: 18px 24px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 20px;
                align-items: center;
            }

            .source-submit-note {
                display: grid;
                grid-template-columns: 40px minmax(0, 1fr);
                gap: 12px;
                align-items: start;
                min-width: 0;
            }

            .source-submit-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                border: 1px solid #bbf7d0;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .source-submit-title {
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #111827;
            }

            .source-submit-copy {
                margin: 4px 0 0;
                max-width: 820px;
                color: #64748b;
                font-size: 13px;
                line-height: 1.55;
            }

            .source-submit-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                flex-wrap: nowrap;
            }

            .source-submit-actions .staff-button {
                height: 42px;
                min-height: 42px;
                min-width: 158px;
                padding: 0 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                white-space: nowrap;
            }

            .source-submit-actions .staff-button-primary {
                min-width: 190px;
            }



            .source-validation-summary {
                border: 1px solid #fecaca;
                background: #fff1f2;
                color: #991b1b;
                border-radius: 12px;
                padding: 14px 16px;
                display: grid;
                gap: 8px;
            }

            .source-validation-title {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #991b1b;
            }

            .source-validation-copy {
                margin: 0;
                font-size: 12.5px;
                line-height: 1.5;
                color: #7f1d1d;
            }

            .source-validation-list {
                margin: 0;
                padding-left: 1.1rem;
                display: grid;
                gap: 4px;
                font-size: 12.5px;
                color: #991b1b;
            }

            .source-invalid-input {
                border-color: #dc2626 !important;
                background: #fff7f7 !important;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.10) !important;
            }

            .source-field.has-error label,
            .source-field-wide.has-error label,
            .source-validation-field.has-error label,
            .source-form-field.has-error label {
                color: #991b1b !important;
            }

            .source-option-card.source-invalid-input,
            .source-option-card.has-error {
                border-color: #dc2626 !important;
                background: #fff7f7 !important;
            }

            .source-inline-error {
                margin-top: 4px;
                display: flex;
                align-items: flex-start;
                gap: 6px;
                color: #991b1b;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.45;
            }

            .source-inline-error i {
                margin-top: 2px;
                font-size: 11px;
                flex: 0 0 auto;
            }

            @media (max-width: 820px) {
                .source-submit-footer {
                    grid-template-columns: 1fr;
                    padding: 16px;
                }

                .source-submit-actions {
                    justify-content: stretch;
                }

                .source-submit-actions .staff-button {
                    flex: 1 1 0;
                    min-width: 0;
                }
            }

            @media (max-width: 520px) {
                .source-submit-note {
                    grid-template-columns: 1fr;
                }

                .source-submit-actions {
                    flex-direction: column-reverse;
                    align-items: stretch;
                }

                .source-submit-actions .staff-button {
                    width: 100%;
                }
            }
        </style>
    </x-slot>


    <section class="staff-scope-banner">
        <div>
            <h3>Single Source Record Encoding</h3>
            <p>
                Use this page only when the source file represents one clear record type. If one file contains connected title, landholding, parcel source, and clearance details, encode it as a Source Package instead. Saving here does not transfer land ownership or mutate registry records.
            </p>
        </div>
        <span class="staff-scope-pill">Indexing Only</span>
    </section>

    <section class="staff-panel staff-panel-pad">
        <h2 class="staff-panel-title">Choose Source Record Type</h2>
        <p class="staff-panel-subtitle">The selected type controls the required fields below.</p>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            @foreach ($recordTypes as $value => $label)
                <a href="{{ route('staff.legacy-records.create', ['record_type' => $value]) }}"
                   class="rounded-xl border p-4 text-sm transition {{ $recordType === $value ? 'border-green-700 bg-green-50 text-green-900' : 'border-gray-200 bg-white text-gray-700 hover:border-green-300 hover:bg-green-50/50' }}">
                    <div class="flex items-center gap-2 font-black">
                        <i class="fa-solid {{ $value === 'title' ? 'fa-file-signature' : ($value === 'landholding' ? 'fa-seedling' : ($value === 'parcel_source' ? 'fa-map-location-dot' : 'fa-stamp')) }}"></i>
                        {{ $label }}
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-gray-600">
                        @if ($value === 'title')
                            Title number and registered owner reference.
                        @elseif ($value === 'landholding')
                            Landholding reference, owner, area, and supporting details.
                        @elseif ($value === 'parcel_source')
                            Parcel code, lot/survey details, and optional geometry reference.
                        @else
                            Historical or previous clearance reference data.
                        @endif
                    </p>
                </a>
            @endforeach
        </div>
    </section>

    <form method="POST" action="{{ route('staff.legacy-records.store') }}" class="space-y-5" data-source-validation-form>
        @csrf
        <input type="hidden" name="record_type" value="{{ $recordType }}">

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Step 1</p>
                <h2 class="staff-panel-title mt-1">Primary Source Details</h2>
                <p class="staff-panel-subtitle">Encode only the key reference data needed for staff review and traceability.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                @if ($recordType === 'title')
                    <div>
                        <label class="staff-form-label">TITLE NUMBER *</label>
                        <input name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">REGISTERED OWNER / LANDOWNER NAME *</label>
                        <input name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">LOT NUMBER</label>
                        <input name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">SURVEY NUMBER</label>
                        <input name="survey_number" value="{{ old('survey_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TITLE / REGISTRATION DATE</label>
                        <input type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                @endif

                @if ($recordType === 'landholding')
                    <div>
                        <label class="staff-form-label">LANDHOLDING REFERENCE NUMBER *</label>
                        <input name="landholding_reference_number" value="{{ old('landholding_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">LANDOWNER NAME *</label>
                        <input name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TITLE NUMBER</label>
                        <input name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">LOT NUMBER</label>
                        <input name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">PREVIOUS DAR REFERENCE NUMBER</label>
                        <input name="previous_dar_reference_number" value="{{ old('previous_dar_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">RECORD DATE</label>
                        <input type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                @endif

                @if ($recordType === 'parcel_source')
                    <div>
                        <label class="staff-form-label">PARCEL REFERENCE CODE *</label>
                        <input name="parcel_code" value="{{ old('parcel_code') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">OWNER / LANDOWNER NAME *</label>
                        <input name="landowner_name" value="{{ old('landowner_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">LOT NUMBER *</label>
                        <input name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">SURVEY NUMBER</label>
                        <input name="survey_number" value="{{ old('survey_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TITLE NUMBER</label>
                        <input name="title_number" value="{{ old('title_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                @endif

                @if ($recordType === 'historical_clearance')
                    <div>
                        <label class="staff-form-label">CLEARANCE CONTROL NUMBER *</label>
                        <input name="control_number" value="{{ old('control_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TRANSFEROR NAME *</label>
                        <input name="transferor_name" value="{{ old('transferor_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TRANSFEREE NAME *</label>
                        <input name="transferee_name" value="{{ old('transferee_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">CLEARANCE / RECORD DATE *</label>
                        <input type="date" name="record_date" value="{{ old('record_date') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">APPLICATION REFERENCE NUMBER</label>
                        <input name="application_reference_number" value="{{ old('application_reference_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">LOT NUMBER</label>
                        <input name="lot_number" value="{{ old('lot_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                @endif
            </div>
        </section>

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Step 2</p>
                <h2 class="staff-panel-title mt-1">Scope and Parcel Link</h2>
                <p class="staff-panel-subtitle">Link only when staff has confirmed the source record refers to an existing main Parcel Record.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div>
                    <label class="staff-form-label">SOURCE RECORD SCOPE *</label>
                    <select name="source_record_scope" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        @foreach ($sourceScopes as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_record_scope', 'current_active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="staff-form-label">LINK TO EXISTING PARCEL</label>
                    <select name="parcel_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                        <option value="">Not linked yet</option>
                        @foreach ($parcels as $parcel)
                            <option value="{{ $parcel->id }}" @selected((string) old('parcel_id') === (string) $parcel->id)>
                                {{ $parcel->parcel_code }}
                                @if ($parcel->title_no) — {{ $parcel->title_no }} @endif
                                @if ($parcel->barangay || $parcel->municipality) — {{ $parcel->barangay ?? 'N/A' }}, {{ $parcel->municipality ?? 'N/A' }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Linking attaches documentary support only. It does not change ownership.</p>
                </div>
            </div>
        </section>

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Step 3</p>
                <h2 class="staff-panel-title mt-1">Parcel / Location Information</h2>
                <p class="staff-panel-subtitle">Optional supporting location and area details copied from the source document.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div>
                    <label class="staff-form-label">AREA IN HECTARES</label>
                    <input type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
                <div>
                    <label class="staff-form-label">CROP / LAND USE</label>
                    <input name="crop_or_land_use" value="{{ old('crop_or_land_use') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
                <div>
                    <label class="staff-form-label">PROVINCE</label>
                    <input name="province" value="{{ old('province', 'Negros Oriental') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
                <div>
                    <label class="staff-form-label">BARANGAY</label>
                    <input name="barangay" value="{{ old('barangay') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
                <div>
                    <label class="staff-form-label">MUNICIPALITY</label>
                    <input name="municipality" value="{{ old('municipality') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                </div>
            </div>
        </section>

        <section class="staff-panel overflow-hidden">
            <div class="staff-panel-pad border-b border-gray-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-green-700">Step 4</p>
                <h2 class="staff-panel-title mt-1">Geometry and Provenance</h2>
                <p class="staff-panel-subtitle">Geometry is stored only as source reference data unless staff later creates or links a main Parcel Record.</p>
            </div>

            <div class="p-6 space-y-5">
                <div>
                    <label class="staff-form-label">BOUNDARY / TECHNICAL DESCRIPTION NOTES</label>
                    <textarea name="boundary_description" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('boundary_description') }}</textarea>
                </div>
                <div>
                    <label class="staff-form-label">SOURCE GEOJSON GEOMETRY</label>
                    <textarea name="source_geometry_geojson" rows="6" class="w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-green-600 focus:ring-green-600" placeholder='Example polygon GeoJSON'>{{ old('source_geometry_geojson') }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="staff-form-label">SOURCE BOOK / FILE *</label>
                        <input name="source_book" value="{{ old('source_book') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">PAGE NUMBER</label>
                        <input name="page_number" value="{{ old('page_number') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TRANSCRIBED BY *</label>
                        <input name="transcribed_by" value="{{ old('transcribed_by', auth()->user()->name) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                    <div>
                        <label class="staff-form-label">TRANSCRIPTION DATE *</label>
                        <input type="date" name="transcription_date" value="{{ old('transcription_date', now()->toDateString()) }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="staff-form-label">REMARKS</label>
                        <textarea name="remarks" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('remarks') }}</textarea>
                    </div>
                    <div>
                        <label class="staff-form-label">SOURCE NOTES</label>
                        <textarea name="source_notes" rows="3" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-600 focus:ring-green-600">{{ old('source_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="source-submit-footer">
                <div class="source-submit-note">
                    <span class="source-submit-icon" aria-hidden="true">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>

                    <div>
                        <p class="source-submit-title">Ready to save source record</p>
                        <p class="source-submit-copy">
                            Saving creates an indexed documentary/provenance record for staff review and traceability only. It does not transfer ownership, assign parcels, or mutate registry records.
                        </p>
                    </div>
                </div>

                <div class="source-submit-actions">
                    <a href="{{ route('staff.legacy-records.index') }}" class="staff-button staff-button-light">
                        Cancel
                    </a>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Source Record
                    </button>
                </div>
            </div>
        </section>
    </form>


    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const validationErrors = @json($errors->toArray());
                const form = document.querySelector('[data-source-validation-form]');

                if (!form || !validationErrors) {
                    return;
                }

                let firstInvalidElement = null;

                Object.entries(validationErrors).forEach(function ([fieldName, messages]) {
                    const fields = Array.from(form.querySelectorAll('[name]')).filter(function (field) {
                        return field.name === fieldName || field.name === fieldName + '[]';
                    });

                    if (!fields.length) {
                        return;
                    }

                    fields.forEach(function (field) {
                        const isCheckboxOrRadio = field.type === 'checkbox' || field.type === 'radio';
                        const wrapper = field.closest('.source-field, .source-field-wide, .source-validation-field, .source-form-field, div') || field.parentElement;
                        const message = Array.isArray(messages) ? messages[0] : messages;

                        if (!firstInvalidElement) {
                            firstInvalidElement = field;
                        }

                        field.setAttribute('aria-invalid', 'true');

                        if (isCheckboxOrRadio) {
                            const optionCard = field.closest('.source-option-card');
                            if (optionCard) {
                                optionCard.classList.add('has-error', 'source-invalid-input');
                            }
                        } else {
                            field.classList.add('source-invalid-input');
                        }

                        if (wrapper) {
                            wrapper.classList.add('has-error');

                            if (!wrapper.querySelector('[data-validation-for="' + fieldName + '"]')) {
                                const errorLine = document.createElement('p');
                                errorLine.className = 'source-inline-error';
                                errorLine.dataset.validationFor = fieldName;
                                errorLine.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span>' + message + '</span>';
                                wrapper.appendChild(errorLine);
                            }
                        }
                    });
                });

                if (firstInvalidElement) {
                    setTimeout(function () {
                        firstInvalidElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (typeof firstInvalidElement.focus === 'function' && firstInvalidElement.type !== 'hidden') {
                            firstInvalidElement.focus({ preventScroll: true });
                        }
                    }, 120);
                }
            });
        </script>
    @endif

</x-staff-shell>
