@php
    $requirementName = strtolower($req->name ?? '');

    $primaryLabel = 'Document reference number';
    $primaryPlaceholder = 'Enter reference number';

    $showReferenceLot = false;

    if (str_contains($requirementName, 'title')) {
        $primaryLabel = 'Title number';
        $primaryPlaceholder = 'e.g., TCT No., OCT No., CLOA Title No.';
        $showReferenceLot = true;
    } elseif (str_contains($requirementName, 'tax declaration')) {
        $primaryLabel = 'Tax declaration number';
        $primaryPlaceholder = 'e.g., TD No.';
        $showReferenceLot = true;
    } elseif (str_contains($requirementName, 'receipt') || str_contains($requirementName, 'official receipt')) {
        $primaryLabel = 'Official receipt number';
        $primaryPlaceholder = 'e.g., OR No.';
    } elseif (str_contains($requirementName, 'certificate') || str_contains($requirementName, 'certification')) {
        $primaryLabel = 'Certificate / certification number';
        $primaryPlaceholder = 'Enter certificate or certification number';
    } elseif (str_contains($requirementName, 'affidavit')) {
        $primaryLabel = 'Affidavit / document number';
        $primaryPlaceholder = 'Enter affidavit or document reference number';
    }

    $isTransferInstrument = str_contains($requirementName, 'deed')
        || str_contains($requirementName, 'sale')
        || str_contains($requirementName, 'donation')
        || str_contains($requirementName, 'waiver')
        || str_contains($requirementName, 'extrajudicial')
        || str_contains($requirementName, 'settlement')
        || str_contains($requirementName, 'transfer instrument')
        || str_contains($requirementName, 'conveyance')
        || str_contains($requirementName, 'transfer document');

    $isMarpoCertification = str_contains($requirementName, 'marpo')
        || str_contains($requirementName, 'ltc form no. 2');

    $metadata = $doc->document_metadata ?? [];
@endphp

<div style="margin-top:12px; margin-bottom:10px; padding:12px; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb;">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
        <div>
            <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                {{ $primaryLabel }}
            </label>

            <input type="text"
                   name="document_reference_number"
                   value="{{ old('document_reference_number', $doc->document_reference_number ?? '') }}"
                   placeholder="{{ $primaryPlaceholder }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                   {{ $isFinal ? 'disabled' : '' }}
                   title="{{ $isFinal ? $lockMsg : '' }}"
            >
        </div>

        <div>
            <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                Issuing office
            </label>

            <input type="text"
                   name="document_metadata[issuing_office]"
                   value="{{ old('document_metadata.issuing_office', data_get($metadata, 'issuing_office')) }}"
                   placeholder="e.g., Registry of Deeds, Assessor's Office, DAR Office"
                   style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                   {{ $isFinal ? 'disabled' : '' }}
                   title="{{ $isFinal ? $lockMsg : '' }}"
            >
        </div>

        <div>
            <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                Date issued
            </label>

            <input type="date"
                   name="document_metadata[date_issued]"
                   value="{{ old('document_metadata.date_issued', data_get($metadata, 'date_issued')) }}"
                   style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                   {{ $isFinal ? 'disabled' : '' }}
                   title="{{ $isFinal ? $lockMsg : '' }}"
            >
        </div>

        @if ($showReferenceLot)
            <div>
                <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
                    Reference lot / parcel
                </label>

                <input type="text"
                       name="document_metadata[reference_lot_or_parcel]"
                       value="{{ old('document_metadata.reference_lot_or_parcel', data_get($metadata, 'reference_lot_or_parcel')) }}"
                       placeholder="e.g., Lot No., Survey No., Parcel No."
                       style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                       {{ $isFinal ? 'disabled' : '' }}
                       title="{{ $isFinal ? $lockMsg : '' }}"
                >
            </div>
        @endif
    </div>

    @if ($isTransferInstrument)
        <div style="margin-top:12px; padding:12px; border:1px solid #dbe4dd; border-radius:8px; background:#ffffff;">
            <div style="font-weight:700; color:#111827; margin-bottom:4px; font-size:13px;">
                Transfer instrument / deed details
            </div>

            <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
                Encode only the key reference details used during clearance review. These details do not finalize the legal transfer of ownership.
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Document title/type</label>
                    <input type="text" name="document_metadata[transfer_document_title]" value="{{ old('document_metadata.transfer_document_title', data_get($metadata, 'transfer_document_title')) }}" placeholder="e.g., Deed of Sale, Deed of Donation" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Lot number in instrument</label>
                    <input type="text" name="document_metadata[transfer_lot_number]" value="{{ old('document_metadata.transfer_lot_number', data_get($metadata, 'transfer_lot_number')) }}" placeholder="e.g., Lot 123" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transfer area</label>
                    <input type="text" name="document_metadata[transfer_area]" value="{{ old('document_metadata.transfer_area', data_get($metadata, 'transfer_area')) }}" placeholder="e.g., 10,000 sq.m. or 1.0000 ha" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Date of notarization</label>
                    <input type="date" name="document_metadata[notarization_date]" value="{{ old('document_metadata.notarization_date', data_get($metadata, 'notarization_date')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transferor(s)</label>
                    <input type="text" name="document_metadata[transferor_names]" value="{{ old('document_metadata.transferor_names', data_get($metadata, 'transferor_names')) }}" placeholder="Names as written in the instrument" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transferee(s)</label>
                    <input type="text" name="document_metadata[transferee_names]" value="{{ old('document_metadata.transferee_names', data_get($metadata, 'transferee_names')) }}" placeholder="Names/entities as written in the instrument" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div style="grid-column:1 / -1;">
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Notary public</label>
                    <input type="text" name="document_metadata[notary_public]" value="{{ old('document_metadata.notary_public', data_get($metadata, 'notary_public')) }}" placeholder="Name of notary public" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Page No.</label>
                    <input type="text" name="document_metadata[notarial_page_number]" value="{{ old('document_metadata.notarial_page_number', data_get($metadata, 'notarial_page_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Book No.</label>
                    <input type="text" name="document_metadata[notarial_book_number]" value="{{ old('document_metadata.notarial_book_number', data_get($metadata, 'notarial_book_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Document No.</label>
                    <input type="text" name="document_metadata[notarial_document_number]" value="{{ old('document_metadata.notarial_document_number', data_get($metadata, 'notarial_document_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Series</label>
                    <input type="text" name="document_metadata[notarial_series]" value="{{ old('document_metadata.notarial_series', data_get($metadata, 'notarial_series')) }}" placeholder="e.g., 2026" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
                </div>
            </div>
        </div>
    @endif

    @if ($isMarpoCertification)
        <div style="margin-top:12px; padding:12px; border:1px solid #dbe4dd; border-radius:8px; background:#ffffff;">
            <div style="font-weight:700; color:#111827; margin-bottom:4px; font-size:13px;">
                MARPO Certification / LTC Form No. 2 review details
            </div>

            <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
                Encode only the checkbox findings and reference details shown in the submitted MARPO Certification.
            </div>

            <div style="display:grid; gap:8px; margin-bottom:12px;">
                <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                    <input type="checkbox"
                           name="document_metadata[marpo_has_tenants]"
                           value="1"
                           @checked((bool) old('document_metadata.marpo_has_tenants', data_get($metadata, 'marpo_has_tenants')))
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                    <span>There are agricultural tenants/leaseholders, farmworkers, actual tillers, or other workers directly tilling the subject land.</span>
                </label>

                <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                    <input type="checkbox"
                           name="document_metadata[marpo_no_tenants]"
                           value="1"
                           @checked((bool) old('document_metadata.marpo_no_tenants', data_get($metadata, 'marpo_no_tenants')))
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                    <span>There are no agricultural tenants/leaseholders, actual tillers, or other workers directly tilling the subject land.</span>
                </label>

                <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                    <input type="checkbox"
                           name="document_metadata[marpo_no_illegal_conversion]"
                           value="1"
                           @checked((bool) old('document_metadata.marpo_no_illegal_conversion', data_get($metadata, 'marpo_no_illegal_conversion')))
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                    <span>There are no erected/ongoing constructions or non-agricultural development activities warranting illegal conversion/premature conversion action.</span>
                </label>

                <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;">
                    <input type="checkbox"
                           name="document_metadata[marpo_no_conflict_claims]"
                           value="1"
                           @checked((bool) old('document_metadata.marpo_no_conflict_claims', data_get($metadata, 'marpo_no_conflict_claims')))
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                    <span>There are no conflict of claims involving the subject land by and between families or third person claimant.</span>
                </label>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Certification place</label>
                    <input type="text"
                           name="document_metadata[marpo_certification_place]"
                           value="{{ old('document_metadata.marpo_certification_place', data_get($metadata, 'marpo_certification_place')) }}"
                           placeholder="Municipality/City, Province"
                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                </div>

                <div>
                    <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">MARPO / designated personnel</label>
                    <input type="text"
                           name="document_metadata[marpo_designated_personnel]"
                           value="{{ old('document_metadata.marpo_designated_personnel', data_get($metadata, 'marpo_designated_personnel')) }}"
                           placeholder="Signature over printed name"
                           style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                           {{ $isFinal ? 'disabled' : '' }}
                           title="{{ $isFinal ? $lockMsg : '' }}"
                    >
                </div>
            </div>
        </div>
    @endif


    <div style="margin-top:10px;">
        <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">
            Verification notes
        </label>

        <textarea name="document_metadata[verification_notes]"
                  rows="2"
                  placeholder="Optional notes for staff review only"
                  style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;"
                  {{ $isFinal ? 'disabled' : '' }}
                  title="{{ $isFinal ? $lockMsg : '' }}"
        >{{ old('document_metadata.verification_notes', data_get($metadata, 'verification_notes')) }}</textarea>
    </div>
</div>
