@php
    $marpoDocuments = $application->documents
        ->filter(function ($document) {
            $requirementName = strtolower((string) optional($document->requiredDocument)->name);

            return str_contains($requirementName, 'marpo')
                || str_contains($requirementName, 'ltc form no. 2');
        })
        ->values();
@endphp

@if ($marpoDocuments->isNotEmpty())
    <section class="review-panel" id="marpo-certification-summary">
        <div class="review-panel-header">
            <div>
                <h2 class="review-panel-title">LTC Form No. 2 — MARPO Certification Summary</h2>
                <p class="review-panel-subtitle">
                    Summary of encoded MARPO Certification metadata from uploaded supporting documents.
                    This is supporting review context from the uploaded MARPO certification.
                </p>
            </div>
        </div>

        <div class="review-panel-body" style="display:grid; gap:12px;">
            @foreach ($marpoDocuments as $document)
                @php
                    $metadata = (array) ($document->document_metadata ?? []);

                    $findings = [
                        'marpo_has_tenants' => 'There are agricultural tenants/leaseholders, farmworkers, actual tillers, or other workers directly tilling the subject land.',
                        'marpo_no_tenants' => 'There are no agricultural tenants/leaseholders, actual tillers, or other workers directly tilling the subject land.',
                        'marpo_no_illegal_conversion' => 'There are no erected/ongoing constructions or non-agricultural development activities warranting illegal conversion/premature conversion action.',
                        'marpo_no_conflict_claims' => 'There are no conflict of claims involving the subject land by and between families or third person claimant.',
                    ];

                    $checkedFindings = collect($findings)
                        ->filter(fn ($label, $key) => (bool) data_get($metadata, $key))
                        ->values();
                @endphp

                <article style="border:1px solid #d1d5db; border-radius:12px; background:#ffffff; padding:16px;">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <h3 style="margin:0; font-size:15px; font-weight:900; color:#111827;">
                                {{ optional($document->requiredDocument)->name ?? 'MARPO Certification / LTC Form No. 2' }}
                            </h3>
                            <p style="margin:4px 0 0; font-size:12px; color:#6b7280;">
                                {{ $document->original_filename ?: 'Metadata-only record' }}
                            </p>
                        </div>

                        @if ($document->annex_reference)
                            <span class="staff-badge staff-badge-green">Annex {{ $document->annex_reference }}</span>
                        @endif
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; margin-bottom:12px;">
                        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:10px;">
                            <p style="margin:0 0 4px; font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase;">Certification Place</p>
                            <p style="margin:0; font-size:13px; color:#111827;">{{ data_get($metadata, 'marpo_certification_place') ?: '—' }}</p>
                        </div>

                        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:10px;">
                            <p style="margin:0 0 4px; font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase;">MARPO / Designated Personnel</p>
                            <p style="margin:0; font-size:13px; color:#111827;">{{ data_get($metadata, 'marpo_designated_personnel') ?: '—' }}</p>
                        </div>
                    </div>

                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px;">
                        <p style="margin:0 0 8px; font-size:12px; font-weight:900; color:#166534; text-transform:uppercase;">Encoded MARPO Findings</p>

                        @if ($checkedFindings->isNotEmpty())
                            <ul style="margin:0; padding-left:20px; color:#166534; font-size:13px; line-height:1.5;">
                                @foreach ($checkedFindings as $finding)
                                    <li>{{ $finding }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p style="margin:0; font-size:13px; color:#166534;">
                                No MARPO findings have been encoded yet. Open the document metadata panel to encode Form No. 2 details.
                            </p>
                        @endif
                    </div>

                    <div style="margin-top:10px; font-size:12px; color:#6b7280;">
                        Form No. 2 remains a supporting certification/reference document.
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
