function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function hiddenCsrf() {
    return `<input type="hidden" name="_token" value="${csrfToken()}">`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function applicationIdFromPath() {
    const match = window.location.pathname.match(/^\/staff\/applications\/(\d+)\/?$/);
    return match ? match[1] : null;
}

function workflowCard(title, copy, body, buttonText, buttonClass = 'staff-button staff-button-primary') {
    return `
        <div class="workflow-decision-heading">
            <span class="workflow-action-icon" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
            <div>
                <p class="workflow-action-title">${title}</p>
                <p class="workflow-action-copy">${copy}</p>
            </div>
        </div>
        ${body || ''}
        <button type="submit" class="${buttonClass}">
            <i class="fa-solid fa-arrow-right"></i>
            ${buttonText}
        </button>
    `;
}

function field(label, name, type = 'text', value = '', attributes = '') {
    return `
        <label style="display:grid;gap:6px;font-size:12px;font-weight:800;color:#334155;">
            <span>${label}</span>
            <input type="${type}" name="${name}" value="${escapeHtml(value)}" class="review-input" ${attributes}>
        </label>
    `;
}

function textareaField(label, name, value = '', attributes = '') {
    return `
        <label style="display:grid;gap:6px;font-size:12px;font-weight:800;color:#334155;">
            <span>${label}</span>
            <textarea name="${name}" rows="3" class="review-input" style="height:auto;padding-top:10px;" ${attributes}>${escapeHtml(value)}</textarea>
        </label>
    `;
}

function configureAdvanceForm(state) {
    const form = document.querySelector('form[action$="/submit"]');
    if (!form || !state.next_status) return;

    const title = form.querySelector('.workflow-action-title');
    const copy = form.querySelector('.workflow-action-copy');
    const note = form.querySelector('.workflow-decision-note');
    const button = form.querySelector('button[type="submit"]');

    if (title) title.textContent = `Advance to ${state.next_status_label}`;
    if (button) button.lastChild.textContent = ` ${state.next_status === 'awaiting_payment' ? 'Complete Intake' : 'Advance Stage'}`;

    const actions = form.querySelector('.workflow-decision-actions');
    if (!actions) return;

    const fields = document.createElement('div');
    fields.className = 'workflow-form-fields citizens-charter-stage-fields';

    switch (state.status) {
        case 'pending_legal_review':
        case 'draft':
        case 'pending_review':
            if (copy) copy.textContent = 'Confirm documentary completeness, record the Payment Order, then move the application to the cashier/payment stage.';
            if (note) note.textContent = 'Incomplete applications should be Returned for Compliance instead of being denied.';
            fields.innerHTML = `
                <input type="hidden" name="applicant_is_juridical_entity" value="0">
                <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;color:#334155;">
                    <input type="checkbox" name="applicant_is_juridical_entity" value="1" ${state.applicant_is_juridical_entity ? 'checked' : ''}>
                    Applicant is a juridical entity
                </label>
                ${field('Payment Order reference (optional; system will create one if blank)', 'payment_order_reference', 'text', state.payment_order_reference || '')}
            `;
            break;

        case 'awaiting_payment':
            if (copy) copy.textContent = 'Record the Official Receipt issued after cashier payment before endorsing the application to LTID.';
            if (note) note.textContent = `DAR-LTCMS records the cashier result only. It does not collect payment. Required filing fee: ₱${Number(state.filing_fee || 2000).toLocaleString(undefined, {minimumFractionDigits: 2})}.`;
            fields.innerHTML = [
                field('Official Receipt number', 'or_number', 'text', state.or_number || '', 'required'),
                field('Official Receipt date', 'or_date', 'date', state.or_date || '', 'required'),
                field('Amount paid', 'amount_paid', 'number', state.amount_paid ?? state.filing_fee ?? 2000, 'step="0.01" min="0" required'),
            ].join('');
            break;

        case 'endorsed_lti':
            if (copy) copy.textContent = 'Record that LTID verification is complete and return the application with LTC Form No. 4 to the Legal Division.';
            if (note) note.textContent = 'LTC Form No. 4 remains a verification/recommendation record and is not the final PARPO II decision.';
            break;

        case 'returned_to_legal':
            if (copy) copy.textContent = 'Begin Legal evaluation after the returned LTID verification and completed LTC Form No. 4 have been reviewed.';
            if (note) note.textContent = 'The backend will block this step until LTC Form No. 4 is complete.';
            break;

        case 'legal_evaluation':
            if (copy) copy.textContent = 'Complete Staff Work (CSW) and clearance preparation before sending the case to Chief Legal for final review.';
            if (note) note.textContent = 'CSW is an internal administrative work record. It does not itself approve or deny the clearance.';
            fields.innerHTML = [
                field('CSW reference (optional; system will create one if blank)', 'csw_reference', 'text', state.csw_reference || ''),
                textareaField('CSW notes (optional)', 'csw_notes', state.csw_notes || ''),
            ].join('');
            break;

        case 'endorsed_chief_legal':
            if (copy) copy.textContent = 'Forward the reviewed CSW and clearance folder to PARPO II.';
            if (note) note.textContent = 'Completed Staff Work must already be recorded before this handoff.';
            break;

        case 'endorsed_parpo':
            if (copy) copy.textContent = 'Place the complete folder at PARPO II Decision Pending for the final Approved or Denied decision.';
            if (note) note.textContent = 'The system rechecks requirements, payment, Form No. 4, CSW, parcel links, and assistive hectare validation before this stage.';
            break;

        case 'returned_for_compliance':
            if (copy) copy.textContent = 'Resume Legal completeness review after the lacking requirements have been supplied.';
            if (note) note.textContent = 'This application was not denied. It remains open for compliance and review.';
            break;
    }

    if (fields.children.length) {
        actions.prepend(fields);
    }
}

function addReturnForCompliance(state, applicationId) {
    if (!state.can_return_for_compliance) return;

    const grid = document.querySelector('.workflow-decision-grid');
    if (!grid || grid.querySelector('[data-return-for-compliance]')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/staff/applications/${applicationId}/return-for-compliance`;
    form.className = 'workflow-decision-card not-approved-card';
    form.dataset.returnForCompliance = 'true';
    form.innerHTML = `
        ${hiddenCsrf()}
        <div class="workflow-decision-heading">
            <span class="workflow-action-icon warning" aria-hidden="true"><i class="fa-solid fa-rotate-left"></i></span>
            <div>
                <p class="workflow-action-title">Return for Compliance</p>
                <p class="workflow-action-copy">Return an incomplete application for lacking requirements without recording a final denial.</p>
            </div>
        </div>
        <div class="workflow-decision-actions">
            ${textareaField('Reason / lacking requirements', 'compliance_reason', '', 'required')}
            <div class="workflow-decision-note">The record stays editable and may return to Legal completeness review after compliance.</div>
        </div>
        <button type="submit" class="staff-button staff-button-light"><i class="fa-solid fa-rotate-left"></i> Return for Compliance</button>
    `;
    grid.appendChild(form);
}

function configureFinalDecisionCards(state) {
    const approveForm = document.querySelector('form[action$="/approve"]');
    const denyForm = document.querySelector('form[action$="/not-approved"]');

    if (denyForm) denyForm.hidden = !state.can_finalize_decision;
    if (approveForm) approveForm.hidden = !state.can_finalize_decision;

    if (!state.can_finalize_decision || !approveForm) return;

    approveForm.dataset.decisionConfirm = 'release';
    const title = approveForm.querySelector('.workflow-action-title');
    const copy = approveForm.querySelector('.workflow-action-copy');
    const note = approveForm.querySelector('.workflow-decision-note');
    const button = approveForm.querySelector('button[type="submit"]');

    if (title) title.textContent = 'Approve application';
    if (copy) copy.textContent = 'Record PARPO II’s final Approved decision and generate the immutable GRANTED LTC Form No. 5 output.';
    if (note) note.textContent = 'Approval is final and locks the application. It does not transfer ownership. Client release is recorded separately afterward.';
    if (button) button.innerHTML = '<i class="fa-solid fa-check"></i> Record Final Approval';

    approveForm.addEventListener('submit', () => {
        window.setTimeout(() => {
            const modal = document.getElementById('decision-confirm-modal');
            if (!modal?.classList.contains('is-open')) return;
            const modalTitle = document.getElementById('decision-confirm-title');
            const modalCopy = document.getElementById('decision-confirm-copy');
            const modalWarning = document.getElementById('decision-confirm-warning');
            const modalSubmit = document.getElementById('decision-confirm-submit');
            if (modalTitle) modalTitle.textContent = 'Approve this application?';
            if (modalCopy) modalCopy.textContent = 'This records PARPO II’s final Approved clearance decision and generates LTC Form No. 5.';
            if (modalWarning) modalWarning.textContent = 'This finalizes and locks the application. Release to the client remains a separate administrative step.';
            if (modalSubmit) modalSubmit.textContent = 'Record Final Approval';
        }, 0);
    });

    if (denyForm) {
        const copy = denyForm.querySelector('.workflow-action-copy');
        const note = denyForm.querySelector('.workflow-decision-note');
        if (copy) copy.textContent = 'Record PARPO II’s final Denied decision and generate the immutable DENIED LTC Form No. 5 output.';
        if (note) note.textContent = 'Use this only for the final PARPO II decision. Earlier deficiencies must use Return for Compliance.';
    }
}

function addReleaseTracking(state, applicationId) {
    if (!state.is_final) return;

    const body = document.querySelector('#workflow-modal .workflow-modal-body');
    if (!body || body.querySelector('[data-release-tracking]')) return;

    const wrapper = document.createElement('div');
    wrapper.dataset.releaseTracking = 'true';
    wrapper.style.marginTop = '14px';

    if (state.can_mark_ready_for_release) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/staff/applications/${applicationId}/ready-for-release`;
        form.className = 'workflow-decision-card approve-card';
        form.innerHTML = workflowCard(
            'Mark signed output Ready for Release',
            'Use this after the signed/sealed Form No. 5 has been returned to Legal and is ready for client pickup/release.',
            `<div class="workflow-decision-note">The final ${escapeHtml(state.status_label)} decision remains locked and unchanged.</div>${hiddenCsrf()}`,
            'Mark Ready for Release'
        );
        wrapper.appendChild(form);
    } else if (state.can_release_output) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/staff/applications/${applicationId}/release`;
        form.className = 'workflow-decision-card approve-card';
        form.innerHTML = `
            ${hiddenCsrf()}
            <input type="hidden" name="release_confirmation" value="1">
            <div class="workflow-decision-heading">
                <span class="workflow-action-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-document"></i></span>
                <div>
                    <p class="workflow-action-title">Record Release to Client</p>
                    <p class="workflow-action-copy">Record the actual handoff of the signed clearance decision to the client or authorized representative.</p>
                </div>
            </div>
            <div class="workflow-decision-actions">
                <div class="workflow-form-fields">
                    ${field('Recipient / authorized representative', 'release_recipient_name', 'text', '', 'required')}
                    ${field('Logbook reference (optional)', 'release_logbook_reference', 'text', '')}
                    <label style="display:grid;gap:6px;font-size:12px;font-weight:800;color:#334155;">
                        <span>Client Satisfaction Measurement</span>
                        <select name="csm_status" class="review-input">
                            <option value="not_recorded">Not recorded</option>
                            <option value="issued">Issued</option>
                            <option value="received">Received</option>
                        </select>
                    </label>
                </div>
                <div class="workflow-decision-note">This records document delivery only. It does not alter the final decision, parcel ownership, landholding ownership, or registry records.</div>
            </div>
            <button type="submit" class="staff-button staff-button-primary"><i class="fa-solid fa-hand-holding-document"></i> Confirm Release to Client</button>
        `;
        wrapper.appendChild(form);
    } else if (state.release_status === 'released') {
        wrapper.innerHTML = `
            <div class="review-note-box">
                <strong>Released to Client</strong><br>
                Recipient: ${escapeHtml(state.release_recipient_name || 'Recorded recipient')}<br>
                ${state.release_logbook_reference ? `Logbook: ${escapeHtml(state.release_logbook_reference)}<br>` : ''}
                ${state.released_at ? `Recorded: ${escapeHtml(state.released_at)}` : ''}
            </div>
        `;
    }

    if (wrapper.childElementCount) body.appendChild(wrapper);

    const finalCopy = document.querySelector('.final-lock-card .review-panel-subtitle');
    if (finalCopy) {
        finalCopy.textContent = 'The PARPO II decision is final. Application edits and document changes are locked for audit integrity; only authorized release tracking remains available.';
    }
}

function applyRequirementState(state) {
    const evaluation = state.requirements;
    if (!evaluation?.requirements) return;

    evaluation.requirements.forEach((requirement) => {
        const card = document.getElementById(`required-document-${requirement.id}`);
        if (!card) return;

        card.hidden = !requirement.applicable;
        card.classList.toggle('is-missing-blocking', requirement.applicable && requirement.blocking && !requirement.complete);
        card.classList.toggle('is-uploaded', requirement.applicable && requirement.present && requirement.complete);

        if (requirement.applicable && !requirement.freshness_valid && requirement.freshness_message) {
            let warning = card.querySelector('[data-document-validity-warning]');
            if (!warning) {
                warning = document.createElement('div');
                warning.dataset.documentValidityWarning = 'true';
                warning.className = 'review-alert review-alert-error';
                warning.style.margin = '12px';
                card.querySelector('.requirement-main')?.appendChild(warning);
            }
            warning.textContent = requirement.freshness_message;
        }
    });

    const score = document.querySelector('.checklist-compact-score strong');
    if (score) score.textContent = `${evaluation.complete_blocking_count}/${evaluation.blocking_count}`;
}

function lockForm4OutsideReviewStage(state) {
    const section = document.getElementById('ltc-form-no-4-review');
    if (!section || state.form4_editable || state.is_final) return;

    section.querySelectorAll('input, textarea, select, button[type="submit"]').forEach((control) => {
        control.disabled = true;
        control.title = 'LTC Form No. 4 is editable only during LTID verification or returned-to-Legal review.';
    });

    const body = section.querySelector('.review-panel-body') || section;
    if (!body.querySelector('[data-form4-stage-lock]')) {
        const note = document.createElement('div');
        note.dataset.form4StageLock = 'true';
        note.className = 'review-note-box';
        note.style.marginBottom = '12px';
        note.textContent = 'Form No. 4 is read-only at this stage. It may be edited only during LTID verification or the returned-to-Legal review stage.';
        body.prepend(note);
    }
}

async function initCitizensCharterFlow() {
    const applicationId = applicationIdFromPath();
    if (!applicationId) return;

    let response;
    try {
        response = await fetch(`/staff/applications/${applicationId}/workflow-state`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
    } catch (error) {
        return;
    }

    if (!response.ok) return;
    const state = await response.json();

    configureAdvanceForm(state);
    addReturnForCompliance(state, applicationId);
    configureFinalDecisionCards(state);
    addReleaseTracking(state, applicationId);
    applyRequirementState(state);
    lockForm4OutsideReviewStage(state);

    const modalCopy = document.querySelector('#workflow-modal .workflow-modal-copy');
    if (modalCopy) {
        modalCopy.textContent = state.is_final
            ? 'Final decision is locked. Complete only the authorized release-tracking steps below.'
            : 'Advance the application only after the current Citizen’s Charter stage is complete.';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCitizensCharterFlow, { once: true });
} else {
    initCitizensCharterFlow();
}
