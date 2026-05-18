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

    $metadata = $doc->document_metadata ?? [];
@endphp

<div style="margin-top:12px; margin-bottom:10px; padding:12px; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb;">
    <div style="font-weight:600; color:#111827; margin-bottom:4px;">
        Indexing details
    </div>

    <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
        Encode selected reference details for staff review and auditability only. This is not full transcription or final legal verification.
    </div>

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

    <div>
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