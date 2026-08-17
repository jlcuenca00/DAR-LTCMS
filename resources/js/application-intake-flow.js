import '../css/application-intake-flow.css';

function normalizedPath() {
    const path = window.location.pathname.replace(/\/+$/, '');
    return path || '/';
}

function createElement(tag, className, html = '') {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (html) element.innerHTML = html;
    return element;
}

function applicationsIndexPath(path) {
    return path === '/staff/applications';
}

function applicationCreatePath(path) {
    return path === '/staff/applications/create';
}

function applicationShowPath(path) {
    return /^\/staff\/applications\/\d+$/.test(path);
}

function buildMiniPath() {
    return `
        <div class="intake-mini-path" aria-label="Recommended clearance intake order">
            <div class="intake-mini-step">
                <span class="intake-mini-number">1</span>
                <span><strong>Start the application</strong><span>Encode the filing and the names shown in the submitted records.</span></span>
            </div>
            <div class="intake-mini-step">
                <span class="intake-mini-number">2</span>
                <span><strong>Link what already exists</strong><span>Use existing Landowner and Parcel Records when they are already in the system.</span></span>
            </div>
            <div class="intake-mini-step">
                <span class="intake-mini-number">3</span>
                <span><strong>Complete missing links</strong><span>After saving, the application page shows which master-record links still need attention.</span></span>
            </div>
            <div class="intake-mini-step">
                <span class="intake-mini-number">4</span>
                <span><strong>Process the clearance</strong><span>Requirements, endorsements, decision, and output remain separate staff actions.</span></span>
            </div>
        </div>
    `;
}

function initApplicationsIndex() {
    const toolbar = document.querySelector('.records-toolbar');
    const panel = toolbar?.closest('.staff-panel');
    if (!toolbar || !panel || document.querySelector('[data-application-intake-entry]')) return;

    const createLink = toolbar.querySelector('a[href="/staff/applications/create"], a[href$="/staff/applications/create"]');
    if (createLink) {
        createLink.innerHTML = '<i class="fa-solid fa-plus"></i> Start Clearance Application';
    }

    const card = createElement('section', 'intake-entry-card');
    card.dataset.applicationIntakeEntry = 'true';
    card.innerHTML = `
        <div class="intake-entry-copy">
            <p class="intake-kicker">Recommended starting point</p>
            <h2 class="intake-entry-title">New clearance? Start with the application.</h2>
            <p class="intake-entry-text">
                You do not need to pre-create every Landowner, Parcel, or Landholding Record before encoding a clearance case.
                Start the application, link existing records when they are available, then complete any missing links from the saved application.
            </p>
            <div class="intake-entry-actions">
                <a href="/staff/applications/create" class="staff-button staff-button-primary">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    Start Clearance Application
                </a>
                <a href="/staff/records/landowners" class="staff-button staff-button-light">
                    Master Records
                </a>
            </div>
        </div>
        ${buildMiniPath()}
    `;

    panel.parentNode.insertBefore(card, panel);
}

function sectionByTitle(root, title) {
    return Array.from(root.querySelectorAll('.form-section')).find((section) => {
        return section.querySelector('.section-title')?.textContent.trim() === title;
    }) || null;
}

function numberSection(section, stepLabel) {
    if (!section || section.querySelector('.intake-step-badge')) return;

    const head = section.querySelector('.section-head');
    const title = head?.querySelector('.section-title');
    const copy = head?.querySelector('.section-copy');
    if (!head || !title) return;

    head.classList.add('intake-numbered-head');
    const wrapper = createElement('div', 'intake-step-heading');
    const badge = createElement('span', 'intake-step-badge');
    badge.textContent = stepLabel;

    const text = createElement('div');
    title.parentNode.insertBefore(wrapper, title);
    wrapper.appendChild(badge);
    wrapper.appendChild(text);
    text.appendChild(title);
    if (copy) text.appendChild(copy);
}

function appendNote(section, html) {
    if (!section || section.querySelector('.intake-field-note')) return;
    const note = createElement('div', 'intake-field-note', html);
    section.appendChild(note);
}

function initApplicationCreate() {
    const root = document.querySelector('.application-create-page');
    const form = root?.querySelector('form.form-shell');
    if (!root || !form || document.querySelector('[data-application-intake-guide]')) return;

    const headerCopy = form.querySelector('.form-header p');
    if (headerCopy) {
        headerCopy.textContent = 'Start the clearance case here. Encode the submitted information first, link existing master records when available, and complete any missing record links after saving.';
    }

    const guide = createElement('section', 'intake-flow-guide');
    guide.dataset.applicationIntakeGuide = 'true';
    guide.innerHTML = `
        <div class="intake-flow-header">
            <div>
                <p class="intake-kicker">Application-first intake</p>
                <h2 class="intake-flow-title">Use this page as the starting point for a new clearance case.</h2>
                <p class="intake-flow-copy">The application can be saved even when a Landowner or Parcel Record still needs to be created or linked. The saved case will guide the remaining record work.</p>
            </div>
            <div class="intake-flow-note">
                <strong>No ownership change occurs here.</strong><br>
                Encoding and record linking support clearance processing, monitoring, validation, and traceability only.
            </div>
        </div>
        <div class="intake-flow-steps">
            <div class="intake-flow-step"><strong>1 · Intake</strong><span>Applicant, payment, and application date.</span></div>
            <div class="intake-flow-step"><strong>2 · Parties</strong><span>Transferor and transferee names; link existing records when found.</span></div>
            <div class="intake-flow-step"><strong>3 · Parcel & Location</strong><span>Use an existing Parcel Record when available; otherwise complete it after saving.</span></div>
            <div class="intake-flow-step"><strong>4 · Review Context</strong><span>Transfer instruments, succession, retention, and staff review notes.</span></div>
            <div class="intake-flow-step"><strong>5 · Save & Complete</strong><span>Open the saved case and resolve the readiness checklist before processing.</span></div>
        </div>
    `;

    root.insertBefore(guide, form);

    const intake = sectionByTitle(form, 'Application Intake and Payment Details');
    const parties = sectionByTitle(form, 'Party Records');
    const location = sectionByTitle(form, 'Location and Filing Details');
    const parcel = sectionByTitle(form, 'Parcel Reference');
    const review = sectionByTitle(form, 'Landholding Review Context');
    const remarks = sectionByTitle(form, 'Staff Remarks');

    // Keep the form itself simple, but present location and parcel together before
    // landholding review because they establish the case reference used afterward.
    if (parcel && review && parcel.compareDocumentPosition(review) & Node.DOCUMENT_POSITION_PRECEDING) {
        form.insertBefore(parcel, review);
    }

    numberSection(intake, 'Step 1');
    numberSection(parties, 'Step 2');
    numberSection(location, 'Step 3');
    numberSection(parcel, 'Step 3');
    numberSection(review, 'Step 4');
    numberSection(remarks, 'Step 5');

    const partyCopy = parties?.querySelector('.section-copy');
    if (partyCopy) {
        partyCopy.textContent = 'Enter every transferor and transferee shown in the application. Link an existing Landowner Record when one is available; a missing record does not block initial encoding.';
    }

    appendNote(parties, '<strong>If a person is not yet in Landowner Records:</strong> keep the Landowner Record field unlinked and encode the person name. After saving, the application review page can create and link a separate Landowner Record for that party.');

    const parcelCopy = parcel?.querySelector('.section-copy');
    if (parcelCopy) {
        parcelCopy.textContent = 'Link an existing Parcel Record when it is already encoded. If the parcel is not yet in the system, leave this blank and complete the parcel link from the saved application.';
    }

    appendNote(parcel, '<strong>No matching parcel yet?</strong> Save the application first. The Record Readiness panel on the saved case will take you to Parcel Records and back to the parcel-link section.');

    const footerNote = form.querySelector('.footer-note');
    if (footerNote) {
        footerNote.textContent = 'Saving creates the clearance application under Pending Review by Legal Officer, then opens the case for parcel links, Landowner links, requirement review, and workflow processing. Saving does not transfer ownership or alter registry records.';
    }
}

function statusItem({ state = 'neutral', icon, label, copy, actions = [] }) {
    const actionMarkup = actions.map((action) => {
        const target = action.external ? ' target="_blank" rel="noopener"' : '';
        return `<a class="intake-readiness-action" href="${action.href}"${target}>${action.label}<i class="fa-solid fa-arrow-right"></i></a>`;
    }).join('');

    return `
        <div class="intake-readiness-item is-${state}">
            <span class="intake-readiness-icon"><i class="fa-solid ${icon}"></i></span>
            <div>
                <p class="intake-readiness-label">${label}</p>
                <p class="intake-readiness-state">${copy}</p>
            </div>
            ${actionMarkup ? `<div class="intake-readiness-actions">${actionMarkup}</div>` : ''}
        </div>
    `;
}

function initApplicationShow() {
    const root = document.querySelector('.application-review-page');
    const summaryPanel = root?.querySelector('.application-summary-panel');
    if (!root || !summaryPanel || document.querySelector('[data-intake-readiness]')) return;

    const summarySection = summaryPanel.closest('.review-single-grid') || summaryPanel;
    const parcelSection = root.querySelector('#application-parcels');
    const landownerSection = root.querySelector('#landowner-links');
    const parcelRows = parcelSection?.querySelectorAll('.application-parcel-row') || [];
    const partyCards = landownerSection?.querySelectorAll('.landowner-link-card') || [];
    const unlinkedPartyCards = landownerSection?.querySelectorAll('.landowner-link-card.unlinked') || [];
    const requirementCards = root.querySelectorAll('.requirement-card');
    const encodedRequirementCards = root.querySelectorAll('.requirement-card.is-uploaded');
    const missingBlockingCards = root.querySelectorAll('.requirement-card.is-missing-blocking');
    const finalized = Boolean(root.querySelector('.final-lock-card'));

    const parcelReady = parcelRows.length > 0;
    const partyReady = partyCards.length > 0 && unlinkedPartyCards.length === 0;
    const firstRequirement = root.querySelector('.requirement-card[id]');

    const panel = createElement('section', 'intake-readiness-panel');
    panel.dataset.intakeReadiness = 'true';

    const items = [];
    items.push(statusItem({
        state: 'complete',
        icon: 'fa-file-circle-check',
        label: 'Application Record',
        copy: finalized ? 'Saved and preserved with the final decision record.' : 'Created successfully. Continue the case from this page.',
    }));

    items.push(statusItem({
        state: partyReady ? 'complete' : 'attention',
        icon: partyReady ? 'fa-user-check' : 'fa-user-plus',
        label: 'Party Record Links',
        copy: partyReady
            ? `${partyCards.length} party record link${partyCards.length === 1 ? '' : 's'} ready.`
            : `${unlinkedPartyCards.length} of ${partyCards.length || 0} listed part${partyCards.length === 1 ? 'y' : 'ies'} still need${unlinkedPartyCards.length === 1 ? 's' : ''} a Landowner Record link.`,
        actions: landownerSection ? [{ href: '#landowner-links', label: finalized ? 'View' : (partyReady ? 'Review' : 'Complete Links') }] : [],
    }));

    const parcelActions = [];
    if (parcelSection) parcelActions.push({ href: '#application-parcels', label: finalized ? 'View' : (parcelReady ? 'Review' : 'Link Parcel') });
    if (!finalized && !parcelReady) parcelActions.push({ href: '/staff/records/parcels/create', label: 'Create Parcel', external: true });

    items.push(statusItem({
        state: parcelReady ? 'complete' : 'attention',
        icon: parcelReady ? 'fa-map-location-dot' : 'fa-map-pin',
        label: 'Parcel Reference',
        copy: parcelReady
            ? `${parcelRows.length} parcel reference${parcelRows.length === 1 ? '' : 's'} linked to the application.`
            : 'No Parcel Record is linked yet. Link an existing parcel or create one in Parcel Records.',
        actions: parcelActions,
    }));

    items.push(statusItem({
        state: parcelReady && partyReady ? 'neutral' : 'attention',
        icon: 'fa-layer-group',
        label: 'Current Landholding Context',
        copy: finalized
            ? 'Current landholding information remains a preserved review record.'
            : (parcelReady && partyReady
                ? 'Review hectare shares only when applicable. Synchronizing current transferor landholdings remains an explicit staff action.'
                : 'Complete the relevant parcel and transferor links before synchronizing any current landholding shares.'),
        actions: landownerSection ? [{ href: '#landowner-links', label: 'Review as Applicable' }] : [],
    }));

    items.push(statusItem({
        state: missingBlockingCards.length === 0 ? 'complete' : 'attention',
        icon: missingBlockingCards.length === 0 ? 'fa-folder-open' : 'fa-file-circle-exclamation',
        label: 'Requirement Review',
        copy: requirementCards.length
            ? `${encodedRequirementCards.length} of ${requirementCards.length} requirement entries have details encoded; ${missingBlockingCards.length} required entr${missingBlockingCards.length === 1 ? 'y' : 'ies'} still need attention.`
            : 'No requirement checklist is available for this case.',
        actions: firstRequirement ? [{ href: `#${firstRequirement.id}`, label: 'Open Requirements' }] : [],
    }));

    panel.innerHTML = `
        <div class="intake-readiness-header">
            <div class="intake-readiness-copy">
                <p class="intake-kicker">Application-first intake</p>
                <h2 class="intake-readiness-title">Record Readiness</h2>
                <p class="intake-readiness-subtitle">
                    Use this checklist to finish the record links and review information needed for clearance processing.
                    These checks support administrative processing and traceability only; they do not execute or finalize ownership transfer.
                </p>
            </div>
            <span class="intake-readiness-badge"><i class="fa-solid fa-route"></i>${finalized ? 'Final Record Snapshot' : 'Next Actions'}</span>
        </div>
        <div class="intake-readiness-list">${items.join('')}</div>
    `;

    summarySection.insertAdjacentElement('afterend', panel);

    panel.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) return;
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.replaceState(null, '', link.getAttribute('href'));
        });
    });
}

function initApplicationIntakeFlow() {
    const path = normalizedPath();

    if (applicationsIndexPath(path)) initApplicationsIndex();
    if (applicationCreatePath(path)) initApplicationCreate();
    if (applicationShowPath(path)) initApplicationShow();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApplicationIntakeFlow, { once: true });
} else {
    initApplicationIntakeFlow();
}
