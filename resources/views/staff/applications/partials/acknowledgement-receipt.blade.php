@php
    $acknowledgementBlockingRequirements = $blockingRequirements ?? $transferorRequirements->concat($transfereeRequirements)
        ->filter(fn ($requirement) => method_exists($requirement, 'blocksAcceptance') ? $requirement->blocksAcceptance() : (bool) $requirement->is_mandatory);

    $acknowledgementEncodedCount = $acknowledgementBlockingRequirements
        ->filter(fn ($requirement) => $uploaded->has($requirement->id))
        ->count();

    $acknowledgementBlockingTotal = $acknowledgementBlockingRequirements->count();
    $acknowledgementComplete = $acknowledgementBlockingTotal === 0
        || $acknowledgementEncodedCount >= $acknowledgementBlockingTotal;
@endphp

<style>
    .ltc-form3-output-toolbar {
        display: none;
    }

    .ltc-form3-output-toolbar .review-panel-header {
        align-items: center;
        padding-block: 14px;
    }

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
                Use the requirement cards below as the working checklist. LTC Form No. 3 is generated from the current encoded requirement data.
            </p>
        </div>

        <div class="ltc-form3-output-actions">
            <span class="ltc-form3-output-status">
                {{ $acknowledgementEncodedCount }} / {{ $acknowledgementBlockingTotal }} required encoded
            </span>

            <a href="{{ route('staff.applications.acknowledgement.pdf', $application) }}"
               class="staff-button staff-button-primary"
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
        toolbar.style.display = '';
    });
</script>
