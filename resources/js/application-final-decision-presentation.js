function normalizeFinalDecisionPresentation() {
    const page = document.querySelector('.application-review-page');
    if (!page) return;

    const finalLockBadge = page.querySelector('.final-lock-card .staff-badge');
    const outputBadge = page.querySelector('.final-clearance-title-row .staff-badge');
    const outputSubtitle = page.querySelector('.final-clearance-title-row .review-panel-subtitle');

    if (!finalLockBadge || !outputBadge) return;

    const decision = finalLockBadge.textContent.trim().toLowerCase();
    const approved = decision === 'approved' || decision.includes('released');
    const denied = decision === 'denied' || decision.includes('not approved');

    if (!approved && !denied) return;

    outputBadge.textContent = approved ? 'APPROVED' : 'DENIED';
    outputBadge.classList.remove(
        'staff-badge-green',
        'staff-badge-red',
        'staff-badge-amber',
        'staff-badge-slate',
        'staff-badge-gray',
        'staff-badge-blue'
    );
    outputBadge.classList.add(approved ? 'staff-badge-green' : 'staff-badge-red');

    if (outputSubtitle) {
        outputSubtitle.textContent = approved
            ? 'Immutable LTC Form No. 5 output generated from the final PARPO II Approved decision.'
            : 'Immutable LTC Form No. 5 output generated from the final PARPO II Denied decision.';
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', normalizeFinalDecisionPresentation, { once: true });
} else {
    normalizeFinalDecisionPresentation();
}
