@php
    $requirementName = strtolower($req->name ?? '');
    $metadata = $doc->document_metadata ?? [];

    $isTitle = str_contains($requirementName, 'title');
    $isTaxDeclaration = str_contains($requirementName, 'tax declaration');
    $isReceipt = str_contains($requirementName, 'receipt') || str_contains($requirementName, 'official receipt');
    $isAffidavit = str_contains($requirementName, 'affidavit');
    $isCertificate = str_contains($requirementName, 'certificate') || str_contains($requirementName, 'certification');
    $isTransferInstrument = str_contains($requirementName, 'deed') || str_contains($requirementName, 'sale') || str_contains($requirementName, 'donation') || str_contains($requirementName, 'waiver') || str_contains($requirementName, 'extrajudicial') || str_contains($requirementName, 'settlement') || str_contains($requirementName, 'transfer instrument') || str_contains($requirementName, 'conveyance') || str_contains($requirementName, 'transfer document');
    $needsNotarialDetails = $isAffidavit || $isTransferInstrument;

    $primaryKey = $isTitle ? 'title_number' : ($isTaxDeclaration ? 'tax_declaration_number' : 'document_number');
    $primaryLabel = $isTitle ? 'Title number' : ($isTaxDeclaration ? 'Tax declaration number' : ($isReceipt ? 'Official receipt number' : ($isCertificate ? 'Certificate number' : 'Document number')));
    $ownerKey = $isTitle ? 'title_owner_names' : ($isReceipt ? 'payor_or_owner_name' : 'document_owner_names');
    $ownerLabel = $isTitle ? 'Registered owner/s on title' : ($isReceipt ? 'Payor / owner name on receipt' : 'Name/s appearing in document');
    $showOwnerNames = $isTitle || $isTaxDeclaration || $isReceipt || $isCertificate || $isAffidavit;
    $isMarpoCertification = str_contains($requirementName, 'marpo') || str_contains($requirementName, 'ltc form no. 2');
@endphp

<div style="margin-top:12px; margin-bottom:10px; padding:12px; border:1px solid #bbf7d0; border-radius:10px; background:#f0fdf4;">
    <div style="font-weight:800; color:#14532d; margin-bottom:4px; font-size:13px;">Requirement data fields</div>
    <div style="font-size:12px; color:#4b5563; margin-bottom:10px;">Fill out the data shown in the requirement document. File upload is optional/supporting only.</div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
        <div>
            <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">{{ $primaryLabel }}</label>
            <input type="text" name="document_metadata[{{ $primaryKey }}]" value="{{ old('document_metadata.' . $primaryKey, data_get($metadata, $primaryKey)) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
        </div>

        <div>
            <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Date issued</label>
            <input type="date" name="document_metadata[date_issued]" value="{{ old('document_metadata.date_issued', data_get($metadata, 'date_issued')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
        </div>

        @if ($isTitle || $isTaxDeclaration)
            <div style="grid-column:1 / -1;">
                <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Lot / parcel shown in document</label>
                <input type="text" name="document_metadata[reference_lot_or_parcel]" value="{{ old('document_metadata.reference_lot_or_parcel', data_get($metadata, 'reference_lot_or_parcel')) }}" placeholder="Lot No., Survey No., Parcel No." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">
            </div>
        @endif

        @if ($showOwnerNames)
            <div style="grid-column:1 / -1;">
                <label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">{{ $ownerLabel }}</label>
                <textarea name="document_metadata[{{ $ownerKey }}]" rows="2" placeholder="Use one name per line or separate names with semicolon" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">{{ old('document_metadata.' . $ownerKey, data_get($metadata, $ownerKey)) }}</textarea>
            </div>
        @endif
    </div>

    @if ($isTransferInstrument)
        <div style="margin-top:12px; padding:12px; border:1px solid #dbe4dd; border-radius:8px; background:#ffffff;">
            <div style="font-weight:700; color:#111827; margin-bottom:4px; font-size:13px;">Transfer instrument / deed details</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Document title/type</label><input type="text" name="document_metadata[transfer_document_title]" value="{{ old('document_metadata.transfer_document_title', data_get($metadata, 'transfer_document_title')) }}" placeholder="e.g., Deed of Absolute Sale" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transfer area</label><input type="text" name="document_metadata[transfer_area]" value="{{ old('document_metadata.transfer_area', data_get($metadata, 'transfer_area')) }}" placeholder="e.g., 1234 sq.m." style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div style="grid-column:1 / -1;"><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transferor/s in instrument</label><input type="text" name="document_metadata[transferor_names]" value="{{ old('document_metadata.transferor_names', data_get($metadata, 'transferor_names')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div style="grid-column:1 / -1;"><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Transferee/s in instrument</label><input type="text" name="document_metadata[transferee_names]" value="{{ old('document_metadata.transferee_names', data_get($metadata, 'transferee_names')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
            </div>
        </div>
    @endif

    @if ($needsNotarialDetails)
        <div style="margin-top:12px; padding:12px; border:1px solid #dbe4dd; border-radius:8px; background:#ffffff;">
            <div style="font-weight:700; color:#111827; margin-bottom:4px; font-size:13px;">Notarial details</div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div style="grid-column:1 / -1;"><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Notarizer / lawyer name</label><input type="text" name="document_metadata[notary_public]" value="{{ old('document_metadata.notary_public', data_get($metadata, 'notary_public')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Date notarized</label><input type="date" name="document_metadata[notarization_date]" value="{{ old('document_metadata.notarization_date', data_get($metadata, 'notarization_date')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Document No.</label><input type="text" name="document_metadata[notarial_document_number]" value="{{ old('document_metadata.notarial_document_number', data_get($metadata, 'notarial_document_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Page No.</label><input type="text" name="document_metadata[notarial_page_number]" value="{{ old('document_metadata.notarial_page_number', data_get($metadata, 'notarial_page_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Book No.</label><input type="text" name="document_metadata[notarial_book_number]" value="{{ old('document_metadata.notarial_book_number', data_get($metadata, 'notarial_book_number')) }}" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
                <div><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Series</label><input type="text" name="document_metadata[notarial_series]" value="{{ old('document_metadata.notarial_series', data_get($metadata, 'notarial_series')) }}" placeholder="e.g., 2026" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"></div>
            </div>
        </div>
    @endif

    @if ($isMarpoCertification)
        <div style="margin-top:12px; padding:12px; border:1px solid #dbe4dd; border-radius:8px; background:#ffffff;">
            <div style="font-weight:700; color:#111827; margin-bottom:4px; font-size:13px;">MARPO Certification / LTC Form No. 2 review details</div>
            <div style="display:grid; gap:8px; margin-bottom:12px;">
                @foreach (['marpo_has_tenants' => 'There are agricultural tenants/leaseholders, farmworkers, actual tillers, or other workers directly tilling the subject land.', 'marpo_no_tenants' => 'There are no agricultural tenants/leaseholders, actual tillers, or other workers directly tilling the subject land.', 'marpo_no_illegal_conversion' => 'There are no erected/ongoing constructions or non-agricultural development activities warranting illegal conversion/premature conversion action.', 'marpo_no_conflict_claims' => 'There are no conflict of claims involving the subject land.'] as $key => $label)
                    <label style="display:flex; gap:8px; align-items:flex-start; font-size:13px; color:#374151;"><input type="checkbox" name="document_metadata[{{ $key }}]" value="1" @checked((bool) old('document_metadata.' . $key, data_get($metadata, $key))) {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}"><span>{{ $label }}</span></label>
                @endforeach
            </div>
        </div>
    @endif

    <div style="margin-top:10px;"><label style="display:block; font-size:12px; color:#374151; margin-bottom:6px;">Verification notes</label><textarea name="document_metadata[verification_notes]" rows="2" placeholder="Optional notes for staff review only" style="width:100%; border:1px solid #d1d5db; border-radius:6px; padding:6px 8px; font-size:14px;" {{ $isFinal ? 'disabled' : '' }} title="{{ $isFinal ? $lockMsg : '' }}">{{ old('document_metadata.verification_notes', data_get($metadata, 'verification_notes')) }}</textarea></div>
</div>
