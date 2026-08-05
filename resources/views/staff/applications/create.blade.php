<x-staff-shell
    title="Encode New Clearance Application"
    active="applications"
    maxWidth="max-w-6xl"
>
    

    <x-slot name="styles">
        <style>
            .application-create-page {
                display: grid;
                gap: 20px;
            }

            .application-create-page .form-shell {
                overflow: hidden;
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 14px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .application-create-page .form-header {
                display: flex;
                justify-content: space-between;
                gap: 18px;
                align-items: flex-start;
                padding: 22px 24px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(180deg, #ffffff 0%, #fbfcfb 100%);
            }

            .application-create-page .form-header h2,
            .application-create-page .section-title {
                margin: 0;
                font-family: var(--heading-font);
                color: #111827;
            }

            .application-create-page .form-header h2 {
                font-size: 19px;
                font-weight: 900;
            }

            .application-create-page .form-header p,
            .application-create-page .section-copy {
                margin: 6px 0 0;
                color: #6b7280;
                font-size: 13px;
                line-height: 1.55;
            }

            .application-create-page .draft-pill {
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 1px solid #bbf7d0;
                background: #f0fdf4;
                color: #166534;
                border-radius: 999px;
                padding: 7px 12px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .application-create-page .form-section {
                padding: 22px 24px;
                border-bottom: 1px solid #e5e7eb;
            }

            .application-create-page .form-section:last-of-type {
                border-bottom: 0;
            }

            .application-create-page .section-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 18px;
                margin-bottom: 18px;
            }

            .application-create-page .section-title {
                font-size: 16px;
                font-weight: 900;
            }

            .application-create-page .field-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px 18px;
            }

            .application-create-page .field-span-2 {
                grid-column: 1 / -1;
            }

            .application-create-page .field-group {
                min-width: 0;
            }

            .application-create-page .field-label {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 6px;
                font-size: 12px;
                font-weight: 900;
                letter-spacing: 0.03em;
                color: #1f2937;
            }

            .application-create-page .required-mark {
                color: #dc2626;
                font-weight: 900;
            }

            .application-create-page .field-help {
                margin: 6px 0 0;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
            }

            .application-create-page .staff-input,
            .application-create-page .staff-select,
            .application-create-page .staff-textarea {
                width: 100%;
                border: 1px solid #cbd5d1;
                border-radius: 9px;
                background: #ffffff;
                color: #111827;
                font-size: 14px;
                outline: none;
                transition: 150ms ease;
            }

            .application-create-page .staff-input,
            .application-create-page .staff-select {
                min-height: 42px;
                height: 42px;
                padding: 0 12px;
            }

            .application-create-page .staff-textarea {
                min-height: 118px;
                padding: 12px;
                resize: vertical;
            }

            .application-create-page .staff-input:focus,
            .application-create-page .staff-select:focus,
            .application-create-page .staff-textarea:focus {
                border-color: #15803d;
                box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
            }

            .application-create-page .subsection-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #f9fafb;
                padding: 16px;
            }

            .application-create-page .subsection-card .field-grid {
                gap: 14px 16px;
            }
            .repeatable-list { display:grid; gap:12px; }
            .repeatable-item { border:1px solid #e5e7eb; border-radius:12px; background:#fff; padding:14px; }
            .repeatable-item-head { display:flex; justify-content:space-between; gap:12px; margin-bottom:12px; color:#14532d; font-size:12px; font-weight:900; text-transform:uppercase; }
            .mini-remove { border:1px solid #fecaca; border-radius:999px; background:#fff1f2; color:#b91c1c; font-size:11px; font-weight:900; padding:5px 9px; cursor:pointer; }
            .mini-add { margin-top:12px; border:1px dashed #86efac; border-radius:10px; background:#f0fdf4; color:#166534; font-size:12px; font-weight:900; padding:9px 12px; cursor:pointer; }

            .application-create-page .form-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 18px 24px;
                background: #f8fafc;
                border-top: 1px solid #e5e7eb;
            }

            .application-create-page .footer-note {
                margin: 0;
                max-width: 620px;
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
            }

            .application-create-page .footer-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
                flex: 0 0 auto;
            }

            @media (max-width: 900px) {
                .application-create-page .field-grid {
                    grid-template-columns: 1fr;
                }

                .application-create-page .form-header,
                .application-create-page .section-head,
                .application-create-page .form-footer {
                    flex-direction: column;
                    align-items: stretch;
                }

                .application-create-page .footer-actions {
                    width: 100%;
                    flex-direction: column-reverse;
                }

                .application-create-page .footer-actions .staff-button {
                    width: 100%;
                }
            }

            @media (max-width: 560px) {
                .application-create-page .form-header,
                .application-create-page .form-section,
                .application-create-page .form-footer {
                    padding-left: 18px;
                    padding-right: 18px;
                }
            }
        </style>
    </x-slot>

    <div class="application-create-page">

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="mb-2 font-bold">Please correct the following:</p>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.applications.store') }}" class="form-shell" data-autosave-key="clearance-application-create" data-autosave-label="clearance application record">
            @csrf

            <div class="form-header">
                <div>
                    <h2>New Clearance Application Record</h2>
                    <p>
                        Encode the parties, location, filing dates, and optional parcel reference. The record will start under Pending Review by Legal Officer after saving.
                    </p>
                </div>
                <span class="draft-pill">
                    <i class="fa-solid fa-file-pen"></i>
                    Pending Legal Review
                </span>
            </div>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Application Intake and Payment Details</h3>
                        <p class="section-copy">Record the applicant, official receipt, and application date used for DAR clearance processing.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field-group">
                        <label for="applicant_type" class="field-label">Applicant Type</label>
                        <select id="applicant_type" name="applicant_type" class="staff-select">
                            <option value="" @selected(old('applicant_type') === null)>Not specified</option>
                            <option value="transferor" @selected(old('applicant_type') === 'transferor')>Transferor</option>
                            <option value="transferee" @selected(old('applicant_type') === 'transferee')>Transferee</option>
                            <option value="authorized_representative" @selected(old('applicant_type') === 'authorized_representative')>Authorized Representative</option>
                            <option value="other" @selected(old('applicant_type') === 'other')>Other</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="applicant_name" class="field-label">Applicant Name</label>
                        <input id="applicant_name" type="text" name="applicant_name" value="{{ old('applicant_name') }}" class="staff-input" placeholder="Name of person filing the application">
                        <p class="field-help">Leave blank to use the transferor name as the applicant.</p>
                    </div>

                    <div class="field-group">
                        <label for="authorized_representative_name" class="field-label">Authorized Representative Name</label>
                        <input id="authorized_representative_name" type="text" name="authorized_representative_name" value="{{ old('authorized_representative_name') }}" class="staff-input" placeholder="Required only when applicable">
                    </div>

                    <div class="field-group">
                        <label for="has_special_power_of_attorney" class="field-label">Special Power of Attorney</label>
                        <select id="has_special_power_of_attorney" name="has_special_power_of_attorney" class="staff-select">
                            <option value="0" @selected(! old('has_special_power_of_attorney'))>Not applicable / Not indicated</option>
                            <option value="1" @selected(old('has_special_power_of_attorney'))>SPA presented / indicated</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="date_of_application" class="field-label">Date of Application</label>
                        <input id="date_of_application" type="date" name="date_of_application" value="{{ old('date_of_application', now()->toDateString()) }}" class="staff-input">
                    </div>

                    <div class="field-group">
                        <label for="or_number" class="field-label">OR Number</label>
                        <input id="or_number" type="text" name="or_number" value="{{ old('or_number') }}" class="staff-input" placeholder="Official Receipt number">
                    </div>

                    <div class="field-group">
                        <label for="or_date" class="field-label">OR Date</label>
                        <input id="or_date" type="date" name="or_date" value="{{ old('or_date') }}" class="staff-input">
                    </div>

                    <div class="field-group">
                        <label for="amount_paid" class="field-label">Amount Paid (PHP)</label>
                        <input id="amount_paid" type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid') }}" class="staff-input" placeholder="0.00">
                    </div>
                </div>
            </section>

<section class="form-section">
    <div class="section-head">
        <div>
            <h3 class="section-title">Party Records</h3>
            <p class="section-copy">Encode one or more transferors and transferees. Link existing landowner records when available.</p>
        </div>
    </div>

    @php
        $oldTransferors = old('transferors', [['landowner_id' => old('transferor_landowner_id'), 'name' => old('transferor_name')]]);
        $oldTransferees = old('transferees', [['landowner_id' => old('transferee_landowner_id'), 'name' => old('transferee_name')]]);
    @endphp

    <div class="field-grid">
        @foreach (['transferors' => ['label' => 'Transferor', 'items' => $oldTransferors], 'transferees' => ['label' => 'Transferee', 'items' => $oldTransferees]] as $partyKey => $partyGroup)
            <div class="subsection-card">
                <div class="repeatable-list" data-party-list="{{ $partyKey }}">
                    @foreach ($partyGroup['items'] as $index => $party)
                        <div class="repeatable-item" data-party-item>
                            <div class="repeatable-item-head">
                                <span>{{ $partyGroup['label'] }} #<span data-party-number>{{ $index + 1 }}</span></span>
                                <button type="button" class="mini-remove" data-remove-party>Remove</button>
                            </div>
                            <div class="field-grid">
                                <div class="field-group field-span-2">
                                    <label class="field-label">{{ $partyGroup['label'] }} Landowner Record</label>
                                    <select name="{{ $partyKey }}[{{ $index }}][landowner_id]" class="staff-select" data-party-landowner-select>
                                        <option value="">No linked landowner record</option>
                                        @foreach ($landowners as $landowner)
                                            <option value="{{ $landowner->id }}" data-name="{{ $landowner->full_name }}" @selected(($party['landowner_id'] ?? null) == $landowner->id)>
                                                {{ $landowner->full_name }} — {{ $landowner->municipality ?? 'No municipality' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field-group field-span-2">
                                    <label class="field-label">{{ $partyGroup['label'] }} Name <span class="required-mark">*</span></label>
                                    <input type="text" name="{{ $partyKey }}[{{ $index }}][name]" value="{{ $party['name'] ?? '' }}" class="staff-input" required data-party-name-input placeholder="Name as written in the application">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="mini-add" data-add-party="{{ $partyKey }}">+ Add another {{ strtolower($partyGroup['label']) }}</button>
            </div>
        @endforeach
    </div>
</section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Location and Filing Details</h3>
                        <p class="section-copy">Record the application location and filing dates for monitoring and report generation.</p>
                    </div>
                </div>

                <div class="field-grid">
                    <div class="field-group">
                        <label for="municipality" class="field-label">Municipality</label>
                        <select id="municipality" name="municipality" class="staff-select" data-location-municipality>
                            <option value="">Select municipality/city</option>
                            @foreach (array_keys($locationOptions ?? []) as $municipality)
                                <option value="{{ $municipality }}" @selected(old('municipality') === $municipality)>{{ $municipality }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="barangay" class="field-label">Barangay</label>
                        <select id="barangay" name="barangay" class="staff-select" data-location-barangay data-old-barangay="{{ old('barangay') }}">
                            <option value="">Select barangay</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="date_of_clearance_release" class="field-label">Date of Releasing of Clearance</label>
                        <input id="date_of_clearance_release" type="date" name="date_of_clearance_release" value="{{ old('date_of_clearance_release') }}" class="staff-input">
                        <p class="field-help">For clearance output tracking only; this does not finalize ownership transfer.</p>
                    </div>

                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Landholding Review Context</h3>
                        <p class="section-copy">Encode the transfer instrument/s directly, then record succession and retention-certificate review context for staff evaluation.</p>
                    </div>
                </div>

                <div class="mt-4 field-grid">                    <div class="field-group field-span-2">
                        <label class="field-label">Transfer Instrument/s</label>
                        @php($oldInstruments = old('transfer_instruments', [['name' => old('transfer_nature') ? (\App\Models\LandTransferApplication::transferNatureOptions()[old('transfer_nature')] ?? old('transfer_nature')) : '']]))
                        <div class="repeatable-list" data-instrument-list>
                            @foreach ($oldInstruments as $index => $instrument)
                                <div class="repeatable-item" data-instrument-item>
                                    <div class="repeatable-item-head">
                                        <span>Instrument #<span data-instrument-number>{{ $index + 1 }}</span></span>
                                        <button type="button" class="mini-remove" data-remove-instrument>Remove</button>
                                    </div>
                                    <input type="text" name="transfer_instruments[{{ $index }}][name]" value="{{ $instrument['name'] ?? '' }}" class="staff-input" placeholder="Example: Deed of Sale, Extrajudicial Settlement, Waiver of Rights">
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="mini-add" data-add-instrument>+ Add another transfer instrument</button>
                        <p class="field-help">Use this for the deed or instrument shown in the clearance output. Add more when the application has more than one instrument.</p>
                    </div>

                    <div class="field-group">
                        <label for="is_succession_case" class="field-label">Succession / Inheritance Case</label>
                        <select id="is_succession_case" name="is_succession_case" class="staff-select">
                            <option value="0" @selected(! old('is_succession_case'))>No / Not indicated</option>
                            <option value="1" @selected(old('is_succession_case'))>Yes, succession exception context</option>
                        </select>
                        <p class="field-help">Use only when the application involves succession/inheritance context for manual review.</p>
                    </div>

                    <div class="field-group">
                        <label for="retention_certificate_required" class="field-label">Retention Certificate</label>
                        <select id="retention_certificate_required" name="retention_certificate_required" class="staff-select">
                            <option value="0" @selected(! old('retention_certificate_required'))>Not required / Not indicated</option>
                            <option value="1" @selected(old('retention_certificate_required'))>Required for this review</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="retention_certificate_reference" class="field-label">Retention Certificate Reference</label>
                        <input id="retention_certificate_reference" type="text" name="retention_certificate_reference" value="{{ old('retention_certificate_reference') }}" class="staff-input" placeholder="Reference/control number, if required">
                        <p class="field-help">If marked required, release will be blocked until a reference is recorded.</p>
                    </div>

                    <div class="field-group field-span-2">
                        <label for="landholding_review_notes" class="field-label">Landholding Review Notes</label>
                        <textarea id="landholding_review_notes" name="landholding_review_notes" rows="3" class="staff-textarea" placeholder="Optional notes about aggregate landholding, succession context, MARPO/LTI review, or retention certificate handling">{{ old('landholding_review_notes') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Parcel Reference</h3>
                        <p class="section-copy">Link a main parcel record for review and reference only.</p>
                    </div>
                </div>

                <div class="mt-4 field-grid">
                    <div class="field-group">
                        <label for="parcel_id" class="field-label">Main Parcel Record</label>
                        <select id="parcel_id" name="parcel_id" class="staff-select">
                            <option value="">No parcel linked yet</option>
                            @foreach ($parcels as $parcel)
                                <option value="{{ $parcel->id }}" data-area="{{ $parcel->area_hectares }}" @selected(old('parcel_id') == $parcel->id)>
                                    {{ $parcel->parcel_code }}
                                    @if ($parcel->title_no)
                                        — {{ $parcel->title_no }}
                                    @endif
                                    @if ($parcel->municipality)
                                        — {{ $parcel->municipality }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="area_hectares" class="field-label">Application Area in Hectares</label>
                        <input id="area_hectares" type="number" step="0.0001" min="0" name="area_hectares" value="{{ old('area_hectares') }}" class="staff-input" placeholder="Leave blank to use parcel area">
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">Staff Remarks</h3>
                        <p class="section-copy">Optional notes for encoding context, document follow-up, or review preparation.</p>
                    </div>
                </div>

                <div class="field-group">
                    <label for="remarks" class="field-label">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" class="staff-textarea" placeholder="Optional staff notes for application encoding">{{ old('remarks') }}</textarea>
                </div>
            </section>

            <div class="form-footer">
                <p class="footer-note">
                    Saving creates a clearance application record under Pending Review by Legal Officer. Endorsement, release, denial, and clearance output generation remain separate staff actions.
                </p>

                <div class="footer-actions">
                    <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">
                        Cancel
                    </a>
                    <button type="submit" class="staff-button staff-button-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Application Record
                    </button>
                </div>
            </div>
        </form>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const landownerOptionsHtml = @json(view('staff.applications.partials.landowner-options', ['landowners' => $landowners])->render());
        const locationOptions = @json($locationOptions ?? []);

        function refreshPartyList(list) {
            if (!list) return;
            const type = list.dataset.partyList;
            const label = type === 'transferors' ? 'Transferor' : 'Transferee';
            list.querySelectorAll('[data-party-item]').forEach(function (item, index) {
                item.querySelector('[data-party-number]').textContent = index + 1;
                item.querySelectorAll('[name]').forEach(function (input) {
                    input.name = input.name.replace(/(transferors|transferees)\[\d+\]/, type + '[' + index + ']');
                });
                const remove = item.querySelector('[data-remove-party]');
                if (remove) remove.style.display = list.querySelectorAll('[data-party-item]').length > 1 ? '' : 'none';
            });
        }

        function wirePartyItem(item) {
            const select = item.querySelector('[data-party-landowner-select]');
            const input = item.querySelector('[data-party-name-input]');
            if (!select || !input) return;
            select.addEventListener('change', function () {
                const selected = select.options[select.selectedIndex];
                if (selected && selected.dataset.name) input.value = selected.dataset.name;
            });
        }

        document.querySelectorAll('[data-party-item]').forEach(wirePartyItem);
        document.querySelectorAll('[data-party-list]').forEach(refreshPartyList);
        document.querySelectorAll('[data-add-party]').forEach(function (button) {
            button.addEventListener('click', function () {
                const type = button.dataset.addParty;
                const list = document.querySelector('[data-party-list="' + type + '"]');
                const label = type === 'transferors' ? 'Transferor' : 'Transferee';
                const index = list.querySelectorAll('[data-party-item]').length;
                const item = document.createElement('div');
                item.className = 'repeatable-item';
                item.setAttribute('data-party-item', '');
                item.innerHTML = `<div class="repeatable-item-head"><span>${label} #<span data-party-number>${index + 1}</span></span><button type="button" class="mini-remove" data-remove-party>Remove</button></div><div class="field-grid"><div class="field-group field-span-2"><label class="field-label">${label} Landowner Record</label><select name="${type}[${index}][landowner_id]" class="staff-select" data-party-landowner-select>${landownerOptionsHtml}</select></div><div class="field-group field-span-2"><label class="field-label">${label} Name <span class="required-mark">*</span></label><input type="text" name="${type}[${index}][name]" class="staff-input" required data-party-name-input placeholder="Name as written in the application"></div></div>`;
                list.appendChild(item);
                wirePartyItem(item);
                refreshPartyList(list);
            });
        });
        document.addEventListener('click', function (event) {
            const removeParty = event.target.closest('[data-remove-party]');
            if (removeParty) {
                const item = removeParty.closest('[data-party-item]');
                const list = item.closest('[data-party-list]');
                if (list.querySelectorAll('[data-party-item]').length > 1) item.remove();
                refreshPartyList(list);
            }
        });

        function refreshInstruments() {
            const list = document.querySelector('[data-instrument-list]');
            if (!list) return;
            list.querySelectorAll('[data-instrument-item]').forEach(function (item, index) {
                item.querySelector('[data-instrument-number]').textContent = index + 1;
                item.querySelector('input').name = 'transfer_instruments[' + index + '][name]';
                item.querySelector('[data-remove-instrument]').style.display = list.querySelectorAll('[data-instrument-item]').length > 1 ? '' : 'none';
            });
        }
        const addInstrument = document.querySelector('[data-add-instrument]');
        if (addInstrument) addInstrument.addEventListener('click', function () {
            const list = document.querySelector('[data-instrument-list]');
            const index = list.querySelectorAll('[data-instrument-item]').length;
            const item = document.createElement('div');
            item.className = 'repeatable-item';
            item.setAttribute('data-instrument-item', '');
            item.innerHTML = `<div class="repeatable-item-head"><span>Instrument #<span data-instrument-number>${index + 1}</span></span><button type="button" class="mini-remove" data-remove-instrument>Remove</button></div><input type="text" name="transfer_instruments[${index}][name]" class="staff-input" placeholder="Example: Deed of Absolute Sale, Extrajudicial Settlement, Waiver of Rights">`;
            list.appendChild(item);
            refreshInstruments();
        });
        document.addEventListener('click', function (event) {
            const remove = event.target.closest('[data-remove-instrument]');
            if (remove) {
                const item = remove.closest('[data-instrument-item]');
                const list = item.closest('[data-instrument-list]');
                if (list.querySelectorAll('[data-instrument-item]').length > 1) item.remove();
                refreshInstruments();
            }
        });
        refreshInstruments();

        const municipalitySelect = document.querySelector('[data-location-municipality]');
        const barangaySelect = document.querySelector('[data-location-barangay]');
        function refreshBarangays() {
            if (!municipalitySelect || !barangaySelect) return;
            const oldBarangay = barangaySelect.dataset.oldBarangay || '';
            barangaySelect.innerHTML = '<option value="">Select barangay</option>';
            (locationOptions[municipalitySelect.value] || []).forEach(function (barangay) {
                const option = document.createElement('option');
                option.value = barangay;
                option.textContent = barangay;
                option.selected = oldBarangay === barangay;
                barangaySelect.appendChild(option);
            });
        }
        if (municipalitySelect) municipalitySelect.addEventListener('change', function () { barangaySelect.dataset.oldBarangay = ''; refreshBarangays(); });
        refreshBarangays();

        const parcelSelect = document.getElementById('parcel_id');
        const areaInput = document.getElementById('area_hectares');
        if (parcelSelect && areaInput) {
            parcelSelect.addEventListener('change', function () {
                const selected = parcelSelect.options[parcelSelect.selectedIndex];
                if (selected && selected.dataset.area && !areaInput.value) areaInput.value = parseFloat(selected.dataset.area).toFixed(4);
            });
        }
    });
</script>


    @include('staff.partials.form-autosave')

</x-staff-shell>
