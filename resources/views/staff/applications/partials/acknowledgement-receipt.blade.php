@php
    $acknowledgementBlockingRequirements = $blockingRequirements ?? $transferorRequirements->concat($transfereeRequirements)
        ->filter(fn ($requirement) => method_exists($requirement, 'blocksAcceptance') ? $requirement->blocksAcceptance() : (bool) $requirement->is_mandatory);

    $acknowledgementEvaluation = $requirementEvaluation ?? app(\App\Services\ApplicationRequirementService::class)->evaluate($application);
    $acknowledgementRows = collect($acknowledgementEvaluation['requirements'])->keyBy('id');
    $acknowledgementEncodedCount = $acknowledgementBlockingRequirements
        ->filter(fn ($requirement) => (bool) data_get($acknowledgementRows->get($requirement->id), 'complete', false))
        ->count();
    $acknowledgementBlockingTotal = $acknowledgementBlockingRequirements->count();
    $acknowledgementComplete = (bool) $acknowledgementEvaluation['complete'];
@endphp

<style>
    .ltc-form3-output-toolbar {
        display: none;
    }

    .ltc-form3-output-toolbar .review-panel-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        padding-block: 14px;
    }

    .ltc-form3-output-progress {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 12px;
        margin-top: 12px;
    }

    .ltc-form3-output-progress-copy { color: #64748b; font-size: 12px; line-height: 1.5; }

    .ltc-form3-output-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ltc-form3-output-status {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
        border: 1px solid {{ $acknowledgementComplete ? '#bbf7d0' : '#fed7aa' }};
        background: {{ $acknowledgementComplete ? '#f0fdf4' : '#fff7ed' }};
        color: {{ $acknowledgementComplete ? '#166534' : '#9a3412' }};
    }

    @media (max-width: 760px) {
        .ltc-form3-output-toolbar .review-panel-header {
            grid-template-columns: minmax(0, 1fr);
            align-items: stretch;
        }

        .ltc-form3-output-actions {
            justify-content: flex-start;
        }
    }
</style>

<section id="ltc-form3-output-toolbar" class="review-panel ltc-form3-output-toolbar" aria-label="Document requirements and LTC Form No. 3 output">
    <div class="review-panel-header">
        <div>
            <h2 class="review-panel-title">Document Requirements</h2>
            <p class="review-panel-subtitle">
                Review and encode the requirements below. LTC Form No. 3 uses the saved checklist data.
            </p>
            <div class="ltc-form3-output-progress">
                <span class="ltc-form3-output-status">
                    {{ $acknowledgementEncodedCount }} / {{ $acknowledgementBlockingTotal }} required complete
                </span>
                @if (! $acknowledgementComplete)
                    <span class="ltc-form3-output-progress-copy">Intake review requires compliance.</span>
                @endif
            </div>
        </div>

        <div class="ltc-form3-output-actions">
            <a href="{{ route('staff.applications.acknowledgement.pdf', $application) }}"
               class="staff-button staff-button-light"
               target="_blank"
               rel="noopener">
                <i class="fa-solid fa-file-pdf"></i>
                Open LTC Form No. 3 PDF
            </a>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toolbar = document.getElementById('ltc-form3-output-toolbar');
        const firstRequirementGroup = document.querySelector('.requirement-group-panel');

        if (! toolbar || ! firstRequirementGroup || ! firstRequirementGroup.parentNode) {
            return;
        }

        firstRequirementGroup.parentNode.insertBefore(toolbar, firstRequirementGroup);
        toolbar.style.display = 'block';
    });
</script>
