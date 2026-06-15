<x-staff-shell
    title="Application Review"
    active="applications"
>
    <x-slot name="actions">
        <a href="{{ route('staff.applications.index') }}" class="staff-button staff-button-light">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Applications
        </a>
    </x-slot>

    <x-slot name="styles">
        <style>
            .application-review-page {
                display: grid;
                gap: 18px;
            }

            .application-review-page h1,
            .application-review-page h2,
            .application-review-page h3,
            .application-review-page h4,
            .application-review-page .review-panel-title,
            .application-review-page .summary-label,
            .application-review-page .summary-value,
            .application-review-page .staff-button,
            .application-review-page .staff-badge,
            .application-review-page label,
            .application-review-page button,
            .application-review-page summary {
                font-family: var(--heading-font) !important;
            }

            .application-review-page p,
            .application-review-page .review-panel-subtitle,
            .application-review-page .requirement-note,
            .application-review-page .document-status-copy,
            .application-review-page .document-form-copy,
            .application-review-page .document-edit-panel-copy,
            .application-review-page input,
            .application-review-page select,
            .application-review-page textarea {
                font-family: var(--body-font) !important;
            }

            .review-alert {
                border-radius: 10px;
                border: 1px solid transparent;
                padding: 13px 16px;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.5;
            }

            .review-alert-success {
                background: #f0fdf4;
                border-color: #bbf7d0;
                color: #166534;
            }

            .review-alert-error {
                background: #fef2f2;
                border-color: #fecaca;
                color: #991b1b;
            }

            .review-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.85fr);
                gap: 18px;
                align-items: start;
            }

            .review-single-grid {
                display: grid;
                gap: 18px;
            }

            .review-panel {
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
                overflow: hidden;
            }

            .review-panel-header {
                padding: 20px 24px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                gap: 16px;
                align-items: flex-start;
            }

            .review-panel-body {
                padding: 22px 24px;
            }

            .review-panel-title {
                margin: 0;
                font-family: var(--heading-font);
                font-size: 17px;
                font-weight: 900;
                color: #111827;
            }

            .review-panel-subtitle {
                margin: 5px 0 0;
                font-size: 13px;
                color: #6b7280;
                line-height: 1.55;
            }

            .summary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .summary-item {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                padding: 14px;
                min-height: 78px;
            }

            .summary-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #64748b;
            }

            .summary-value {
                margin: 7px 0 0;
                font-size: 14px;
                font-weight: 800;
                color: #111827;
                line-height: 1.45;
            }

            .workflow-box {
                display: grid;
                gap: 14px;
            }

            .workflow-form {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                padding: 14px;
                display: grid;
                gap: 12px;
            }

            .workflow-form-fields {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .review-input {
                width: 100%;
                height: 40px;
                border: 1px solid #cbd5d1;
                border-radius: 8px;
                padding: 0 12px;
                font-size: 14px;
                color: #111827;
                background: #ffffff;
            }

            .review-note-box {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                padding: 14px;
                font-size: 13px;
                color: #4b5563;
                line-height: 1.55;
            }

            .clearance-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 14px;
            }


            .final-lock-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
                gap: 16px;
                align-items: stretch;
            }

            .final-lock-card,
            .final-clearance-card {
                border: 1px solid rgba(245, 158, 11, 0.28);
                background: rgba(255, 255, 255, 0.72);
                border-radius: 12px;
                padding: 16px;
            }

            .final-clearance-card {
                background: #ffffff;
                border-color: #dbe4dd;
            }

            .final-clearance-title-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
            }

            .final-clearance-actions {
                margin-top: 14px;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .final-clearance-actions .staff-button {
                width: 100%;
            }

            .completion-card {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 18px;
                align-items: center;
            }

            .completion-number {
                font-family: var(--heading-font);
                font-size: 28px;
                font-weight: 900;
                color: #14532d;
                white-space: nowrap;
            }

            .requirements-section {
                display: grid;
                gap: 14px;
            }

            .requirement-card {
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #ffffff;
                overflow: hidden;
            }

            .requirement-main {
                padding: 0;
                display: grid;
                gap: 0;
            }

            .requirement-heading {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: flex-start;
                border: 0;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(90deg, #f8fffb 0%, #ffffff 82%);
                border-radius: 0;
                padding: 16px 20px;
            }

            .requirement-main > .document-status-panel,
            .requirement-main > .document-upload-panel,
            .requirement-main > .document-locked-note {
                margin: 18px;
            }

            .requirement-title-wrap {
                display: flex;
                align-items: flex-start;
                gap: 11px;
                min-width: 0;
            }

            .requirement-title-icon {
                width: 32px;
                height: 32px;
                border-radius: 9px;
                background: #166534;
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                margin-top: 1px;
                box-shadow: 0 6px 14px rgba(20, 83, 45, 0.14);
            }

            .requirement-type-label {
                margin: 0 0 4px;
                font-family: var(--heading-font) !important;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #15803d;
            }

            .requirement-title {
                margin: 0;
                font-family: var(--heading-font) !important;
                font-size: 18px;
                font-weight: 900;
                color: #064e3b;
                line-height: 1.25;
                letter-spacing: -0.01em;
            }

            .requirement-note {
                margin: 7px 0 0;
                font-size: 12px;
                color: #4b5563;
                line-height: 1.5;
            }

            .document-workflow-grid {
                display: grid;
                grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.35fr);
                gap: 14px;
                align-items: start;
            }

            .document-status-panel,
            .document-indexing-panel,
            .document-upload-panel {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #f8fafc;
                padding: 14px;
            }

            .document-status-panel.uploaded {
                border-color: #bbf7d0;
                background: #f0fdf4;
            }

            .document-status-panel.missing {
                border-color: #fecaca;
                background: #fef2f2;
            }

            .document-status-title,
            .document-form-title {
                margin: 0;
                font-size: 13px;
                font-weight: 900;
                color: #111827;
            }

            .document-status-copy,
            .document-form-copy {
                margin: 5px 0 0;
                font-size: 12px;
                color: #64748b;
                line-height: 1.5;
            }

            .document-action-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
                margin-top: 12px;
            }

            .document-action-row .staff-button,
            .document-action-row button {
                min-height: 38px;
                height: 38px;
            }

            .document-form-section {
                display: grid;
                gap: 12px;
            }

            .document-indexing-panel {
                background: #ffffff;
                margin-top: 12px;
            }

            .document-indexing-header {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e5e7eb;
                margin-bottom: 12px;
            }

            .document-indexing-label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                color: #166534;
                border-radius: 999px;
                padding: 5px 9px;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .document-upload-panel {
                background: #fbfcfd;
            }

            .document-upload-panel.replace {
                border-color: #bfdbfe;
                background: #eff6ff;
            }

            .document-remove-form {
                margin: 0;
            }

            .document-button-row {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
                margin-top: 14px;
            }

            .document-edit-panel {
                margin-top: 14px;
                border: 1px solid #bfdbfe;
                background: #eff6ff;
                border-radius: 10px;
                padding: 14px;
            }

            .document-edit-panel[hidden] {
                display: none !important;
            }

            .document-edit-panel-title {
                margin: 0;
                font-size: 13px;
                font-weight: 900;
                color: #111827;
            }

            .document-edit-panel-copy {
                margin: 5px 0 0;
                font-size: 12px;
                color: #475569;
                line-height: 1.5;
            }

            .document-current-summary {
                margin-top: 12px;
                border: 1px solid #dbe4dd;
                background: #ffffff;
                border-radius: 10px;
                padding: 10px 12px;
                font-size: 12px;
                color: #4b5563;
                line-height: 1.55;
            }

            .document-current-summary-title {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin: 0 0 6px;
                font-size: 12px;
                font-weight: 800;
                color: #111827;
            }

            .document-current-summary-list {
                display: grid;
                gap: 3px;
            }

            .document-current-summary-list div {
                font-size: 12px;
            }

            .document-current-summary-list strong {
                font-weight: 750;
                color: #374151;
            }

            .document-current-summary-meta {
                margin-top: 7px;
                font-size: 11px;
                color: #64748b;
            }

            .document-locked-note {
                margin-top: 12px;
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                padding: 12px;
                color: #64748b;
                font-size: 12px;
                font-weight: 700;
                line-height: 1.5;
            }


            .file-input-wrap label,
            .upload-grid label,
            .document-indexing-panel label {
                font-size: 12px;
                font-weight: 900;
                color: #374151;
            }

            .document-detail-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                font-size: 13px;
                color: #374151;
            }

            .document-detail-item {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 9px;
                padding: 10px 12px;
            }

            .document-file-review {
                margin-top: 12px;
                border: 1px solid #dbe4dd;
                background: #ffffff;
                border-radius: 12px;
                padding: 12px;
                display: grid;
                grid-template-columns: 220px minmax(0, 1fr);
                gap: 14px;
                align-items: stretch;
            }

            .document-file-preview {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                min-height: 150px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .document-file-preview img {
                width: 100%;
                height: 100%;
                max-height: 180px;
                object-fit: cover;
                display: block;
            }

            .document-file-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
            }

            .document-file-info {
                min-width: 0;
                display: grid;
                gap: 10px;
                align-content: center;
            }

            .document-file-label {
                margin: 0;
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #64748b;
            }

            .document-file-name {
                margin: 3px 0 0;
                font-size: 14px;
                font-weight: 850;
                color: #111827;
                line-height: 1.4;
                word-break: break-word;
            }

            .document-file-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .document-file-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                color: #166534;
                border-radius: 999px;
                padding: 5px 8px;
                font-size: 11px;
                font-weight: 850;
            }

            .document-file-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
            }

            .document-file-missing {
                margin-top: 12px;
                border: 1px solid #fecaca;
                background: #fef2f2;
                color: #991b1b;
                border-radius: 10px;
                padding: 11px 12px;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.45;
            }

            .metadata-box {
                margin-top: 10px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 10px;
                padding: 12px;
                font-size: 12px;
                color: #374151;
                line-height: 1.55;
            }

            .upload-form {
                border-top: 1px solid #e5e7eb;
                background: #fbfcfd;
                padding: 18px;
                display: grid;
                gap: 14px;
            }

            .upload-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                padding-inline: 14px;
            }

            .document-form-section > .upload-grid:first-of-type {
                margin-top: -2px;
            }

            .file-input-wrap {
                border: 1px dashed #cbd5d1;
                background: #ffffff;
                border-radius: 10px;
                padding: 14px;
            }

            .file-input-wrap input[type="file"] {
                width: 100%;
                font-size: 13px;
                color: #475569;
                cursor: pointer;
            }

            .file-input-wrap input[type="file"]::file-selector-button {
                margin-right: 12px;
                border: 1px solid #166534;
                border-radius: 8px;
                background: #166534;
                color: #ffffff;
                padding: 8px 12px;
                font-size: 12px;
                font-weight: 800;
                cursor: pointer;
                transition: 150ms ease;
            }

            .file-input-wrap input[type="file"]::file-selector-button:hover {
                background: #14532d;
                border-color: #14532d;
            }

            .file-input-help {
                margin-top: 8px;
                font-size: 11px;
                color: #64748b;
                line-height: 1.45;
            }

            .validation-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .validation-item {
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #f8fafc;
                padding: 13px;
            }

            .validation-item strong {
                display: block;
                font-size: 11px;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #64748b;
                margin-bottom: 6px;
            }

            .source-table-wrap,
            .timeline-table-wrap {
                overflow-x: auto;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
            }

            .timeline-list {
                display: grid;
                gap: 12px;
            }

            .timeline-entry {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 10px;
                padding: 14px;
            }

            .timeline-entry-header {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                align-items: flex-start;
            }

            .timeline-action {
                margin: 0;
                font-size: 14px;
                font-weight: 900;
                color: #111827;
            }

            .timeline-meta {
                margin-top: 5px;
                font-size: 12px;
                color: #6b7280;
                line-height: 1.5;
            }

            .details-summary {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                color: #166534;
                font-size: 13px;
                font-weight: 900;
            }

            .details-pre {
                margin-top: 10px;
                padding: 12px;
                border: 1px solid #e5e7eb;
                background: #ffffff;
                border-radius: 8px;
                color: #334155;
                font-size: 12px;
                line-height: 1.55;
                overflow-x: auto;
            }


            .landowner-link-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                align-items: stretch;
            }

            .landowner-link-card {
                position: relative;
                border: 1px solid #dbe4dd;
                background: #ffffff;
                border-radius: 14px;
                padding: 16px;
                display: grid;
                gap: 14px;
                overflow: hidden;
            }

            .landowner-link-card::before {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                width: 4px;
                background: #e5e7eb;
            }

            .landowner-link-card.linked {
                border-color: #bbf7d0;
                background: linear-gradient(180deg, #f7fef9 0%, #ffffff 72%);
            }

            .landowner-link-card.linked::before {
                background: #16a34a;
            }

            .landowner-link-card.unlinked::before {
                background: #f59e0b;
            }

            .landowner-link-heading {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                align-items: flex-start;
                padding-left: 4px;
            }

            .landowner-link-title {
                margin: 0;
                font-family: var(--heading-font) !important;
                font-size: 16px;
                font-weight: 900;
                color: #111827;
                letter-spacing: -0.01em;
            }

            .landowner-link-copy {
                margin: 5px 0 0;
                font-size: 12.5px;
                color: #64748b;
                line-height: 1.5;
            }

            .landowner-link-name {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                border-radius: 11px;
                padding: 13px 14px;
            }

            .landowner-link-name strong {
                display: block;
                font-size: 10px;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #64748b;
                margin-bottom: 6px;
            }

            .landowner-link-name-value {
                margin: 0;
                font-size: 16px;
                font-weight: 850;
                color: #111827;
            }

            .landowner-linked-note {
                margin-top: 8px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #047857;
                font-size: 12px;
                font-weight: 850;
            }

            .landowner-link-field label {
                display: block;
                margin-bottom: 6px;
                font-size: 10.5px;
                font-weight: 900;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #475569;
            }

            .landowner-link-save-row {
                margin-top: 16px;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 14px;
                padding: 14px 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }

            .landowner-create-grid {
                margin-top: 16px;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .landowner-create-card {
                border: 1px solid #dbe4dd;
                background: #ffffff;
                border-radius: 14px;
                padding: 14px;
                display: grid;
                gap: 11px;
                align-content: start;
                min-height: 118px;
            }

            .landowner-create-title {
                margin: 0;
                font-family: var(--heading-font) !important;
                font-size: 15px;
                font-weight: 900;
                color: #111827;
            }

            .landowner-create-note,
            .landowner-link-save-note {
                margin: 0;
                font-size: 12.5px;
                line-height: 1.55;
                color: #64748b;
            }

            .landowner-link-save-note {
                max-width: 760px;
            }

            .landowner-create-card .staff-button,
            .landowner-link-save-row .staff-button {
                min-height: 42px;
                justify-content: center;
            }

            .landowner-create-card-highlight {
                border-style: solid;
                border-color: #fed7aa;
                background: linear-gradient(180deg, #fffbeb 0%, #ffffff 74%);
            }

            .landowner-create-card-highlight .landowner-create-title {
                color: #92400e;
            }

            .landowner-create-card-disabled {
                border-color: #e5e7eb;
                background: #f8fafc;
                color: #64748b;
            }

            .landowner-create-card-disabled .staff-button {
                pointer-events: none;
                opacity: 0.65;
            }

            .landowner-link-tip {
                margin: 12px 0 0;
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 12px;
                padding: 12px 14px;
                color: #475569;
                font-size: 12.5px;
                line-height: 1.55;
            }

            .landowner-create-card-disabled {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
            }

            .landowner-create-card-disabled .landowner-create-note {
                margin-top: 0.25rem;
            }

            @media (max-width: 760px) {
                .landowner-create-card-disabled {
                    grid-template-columns: 1fr;
                }
            }

            .workflow-bottom-panel {
                border-color: #bbf7d0;
                background: #ffffff;
            }

            .workflow-bottom-panel .review-panel-header {
                background: linear-gradient(90deg, #f0fdf4 0%, #ffffff 78%);
            }

            .workflow-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border: 1px solid #bbf7d0;
                background: #dcfce7;
                color: #14532d;
                border-radius: 999px;
                padding: 7px 11px;
                font-size: 12px;
                font-weight: 900;
                white-space: nowrap;
            }

            .workflow-bottom-panel .workflow-box {
                padding: 18px 20px 20px;
            }

            .workflow-submit-card {
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 12px;
                padding: 16px;
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                gap: 14px;
                align-items: center;
            }

            .workflow-decision-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
                align-items: stretch;
            }

            .workflow-decision-card {
                border: 1px solid #dbe4dd;
                background: #f8faf9;
                border-radius: 12px;
                padding: 16px;
                display: grid;
                grid-template-rows: auto minmax(0, 1fr) auto;
                gap: 14px;
                min-height: 100%;
            }

            .workflow-decision-card.approve-card {
                border-color: #bbf7d0;
                background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            }

            .workflow-decision-card.not-approved-card {
                border-color: #fecaca;
                background: linear-gradient(180deg, #fef2f2 0%, #ffffff 100%);
            }

            .workflow-decision-heading {
                display: flex;
                gap: 13px;
                align-items: flex-start;
            }

            .workflow-action-icon {
                width: 42px;
                height: 42px;
                border-radius: 11px;
                background: #166534;
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .workflow-action-icon.warning {
                background: #dc2626;
            }

            .workflow-action-title {
                margin: 0;
                font-size: 16px;
                font-weight: 900;
                color: #111827;
                line-height: 1.3;
            }

            .workflow-action-copy {
                margin: 6px 0 0;
                font-size: 12.5px;
                line-height: 1.55;
                color: #64748b;
            }

            .workflow-submit-form {
                margin: 0;
                min-width: 220px;
            }

            .workflow-submit-form .staff-button,
            .workflow-decision-card .staff-button {
                width: 100%;
                justify-content: center;
            }

            .workflow-decision-actions {
                display: grid;
                gap: 10px;
            }

            .workflow-decision-actions .workflow-form-fields {
                grid-template-columns: 1fr;
            }

            .workflow-decision-note {
                border: 1px solid #e5e7eb;
                background: rgba(255, 255, 255, 0.76);
                border-radius: 10px;
                padding: 11px 12px;
                font-size: 12px;
                line-height: 1.5;
                color: #4b5563;
            }

            .workflow-decision-card.approve-card .workflow-decision-note {
                border-color: #bbf7d0;
                color: #14532d;
            }

            .workflow-decision-card.not-approved-card .workflow-decision-note {
                border-color: #fecaca;
                color: #991b1b;
            }



            .decision-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 80;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: rgba(15, 23, 42, 0.68);
                backdrop-filter: blur(3px);
            }

            .decision-modal-backdrop.is-open {
                display: flex;
            }

            .decision-modal-card {
                width: min(520px, 100%);
                border: 1px solid #dbe4dd;
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
                overflow: hidden;
                transform: translateY(8px) scale(0.98);
                opacity: 0;
                transition: 160ms ease;
            }

            .decision-modal-backdrop.is-open .decision-modal-card {
                transform: translateY(0) scale(1);
                opacity: 1;
            }

            .decision-modal-header {
                display: flex;
                gap: 14px;
                align-items: flex-start;
                padding: 22px 24px 16px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(90deg, #f8faf9 0%, #ffffff 82%);
            }

            .decision-modal-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border-radius: 13px;
                flex: 0 0 auto;
                background: #dcfce7;
                color: #166534;
            }

            .decision-modal-icon.danger {
                background: #fee2e2;
                color: #b91c1c;
            }

            .decision-modal-title {
                margin: 0;
                font-family: var(--heading-font) !important;
                font-size: 18px;
                font-weight: 900;
                color: #111827;
                line-height: 1.25;
            }

            .decision-modal-copy {
                margin: 6px 0 0;
                font-size: 13px;
                line-height: 1.55;
                color: #64748b;
            }

            .decision-modal-body {
                padding: 18px 24px;
            }

            .decision-modal-warning {
                border: 1px solid #fed7aa;
                background: #fffbeb;
                color: #92400e;
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 12.5px;
                line-height: 1.55;
                font-weight: 750;
            }

            .decision-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 16px 24px 22px;
                border-top: 1px solid #e5e7eb;
                background: #f8fafc;
            }

            .decision-modal-actions .staff-button {
                min-width: 132px;
                justify-content: center;
            }

            .timeline-collapsible { overflow: hidden; }
            .timeline-collapsible > summary { list-style: none; cursor: pointer; }
            .timeline-collapsible > summary::-webkit-details-marker { display: none; }

            .timeline-summary-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px 20px;
                border-bottom: 1px solid transparent;
            }

            .timeline-summary-left {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                min-width: 0;
            }

            .timeline-summary-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                background: #ecfdf5;
                color: #166534;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .timeline-chevron {
                width: 34px;
                height: 34px;
                border-radius: 999px;
                border: 1px solid #d1d5db;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #166534;
                background: #ffffff;
                transition: transform 160ms ease;
                flex: 0 0 auto;
            }

            .timeline-collapsible[open] .timeline-chevron { transform: rotate(180deg); }
            .timeline-collapsible[open] .timeline-summary-row { border-bottom-color: #e5e7eb; }
            @media (max-width: 1180px) {
                .review-grid {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 760px) {
                .review-panel-header,
                .requirement-heading,
                .timeline-entry-header,
                .completion-card {
                    flex-direction: column;
                    display: flex;
                    align-items: flex-start;
                }

                .summary-grid,
                .workflow-form-fields,
                .workflow-decision-grid,
                .landowner-link-grid,
                .landowner-create-grid,
                .landowner-link-select-row,
                .upload-grid,
                .document-detail-grid,
                .document-file-review,
                .document-workflow-grid,
                .validation-grid,
                .final-lock-grid,
                .final-clearance-actions {
                    grid-template-columns: 1fr;
                }

                .document-action-row .staff-button,
                .document-action-row button,
                .document-upload-panel .staff-button {
                    width: 100%;
                }

                .landowner-link-save-row {
                    align-items: stretch;
                    flex-direction: column;
                }

                .landowner-link-save-row .staff-button {
                    width: 100%;
                }

                .review-panel-body,
                .review-panel-header,
                .upload-form {
                    padding: 18px;
                }

                .requirement-main > .document-status-panel,
                .requirement-main > .document-upload-panel,
                .requirement-main > .document-locked-note {
                    margin: 14px;
                }

                .requirement-title-wrap {
                    width: 100%;
                }

                .requirement-title {
                    font-size: 16px;
                }


                .source-link-header,
                .timeline-summary-row {
                    flex-direction: column;
                    align-items: stretch;
                }

                .workflow-submit-card,
                .workflow-decision-card {
                    grid-template-columns: 1fr;
                }

                .workflow-submit-form {
                    width: 100%;
                    min-width: 0;
                }

                .clearance-actions .staff-button,
                .workflow-form .staff-button,
                .upload-form .staff-button {
                    width: 100%;
                }
            }
        </style>
    </x-slot>

    @php
        $isFinal = $application->isFinalized();
        $lockMsg = 'Application finalized. Uploads, removals, and workflow decisions are locked.';
        $statusLabels = method_exists($application, 'statusLabel')
            ? \App\Models\LandTransferApplication::statusLabels()
            : [];
        $statusLabel = method_exists($application, 'statusLabel')
            ? $application->statusLabel()
            : ucwords(str_replace('_', ' ', $application->status));
        $nextWorkflowStatus = method_exists($application, 'nextWorkflowStatus')
            ? $application->nextWorkflowStatus()
            : null;

        if (in_array($application->status, ['draft', 'pending_review'], true)) {
            $nextWorkflowStatus = 'pending_legal_review';
        }

        $nextWorkflowStatusLabel = $nextWorkflowStatus
            ? ($statusLabels[$nextWorkflowStatus] ?? ucwords(str_replace('_', ' ', $nextWorkflowStatus)))
            : null;
        $canAdvanceWorkflow = ! $isFinal
            && $nextWorkflowStatus
            && $nextWorkflowStatus !== 'released';
        $canRelease = ! $isFinal
            && in_array($application->status, ['for_releasing', 'pending_review'], true);
        $canDeny = ! $isFinal
            && in_array($application->status, [
                'draft',
                'pending_review',
                'pending_legal_review',
                'endorsed_lti',
                'endorsed_chief_legal',
                'endorsed_parpo',
                'for_releasing',
            ], true);
        $statusBadgeClass = match ($application->status) {
            'released', 'approved' => 'staff-badge-green',
            'pending_legal_review', 'endorsed_lti', 'endorsed_chief_legal', 'endorsed_parpo', 'for_releasing', 'pending_review' => 'staff-badge-amber',
            'denied', 'not_approved' => 'staff-badge-red',
            'draft' => 'staff-badge-slate',
            default => 'staff-badge-slate',
        };
        $totalReq = $transferorRequirements->count() + $transfereeRequirements->count();
        $uploadedCount = $uploaded->count();

        $requirementGroups = [
            [
                'title' => 'Transferor Requirements (DAR A.O. No. 4, s. 2021)',
                'description' => 'Documents submitted by or for the transferor side of the clearance application.',
                'requirements' => $transferorRequirements,
            ],
            [
                'title' => 'Transferee Requirements (DAR A.O. No. 4, s. 2021)',
                'description' => 'Documents submitted by or for the transferee side of the clearance application.',
                'requirements' => $transfereeRequirements,
            ],
        ];

        $landownerOptions = $landowners ?? \App\Models\Landowner::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(500)
            ->get();

        $applicationAgriculturalStatusLabels = $application->applicationParcels
            ->pluck('parcel')
            ->filter()
            ->map(fn ($parcel) => $parcel->agricultural_status_label)
            ->filter()
            ->unique()
            ->values();
    @endphp

    <div class="application-review-page">
        @if (session('success'))
            <div class="review-alert review-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="review-alert review-alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="review-alert review-alert-error">
                <div class="font-bold mb-2">Workflow action blocked</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isFinal)
            <section class="review-panel" style="border-color:#fcd34d; background:#fffbeb;">
                <div class="review-panel-body">
                    <div class="final-lock-grid">
                        <div class="final-lock-card">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                <div>
                                    <h2 class="review-panel-title">Final Decision Locked</h2>
                                    <p class="review-panel-subtitle">
                                        This application already has a final decision. Uploads, document removals, resubmission,
                                        release, and denial actions are locked for audit integrity.
                                    </p>
                                </div>
                                <span class="staff-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                            </div>

                            @if ($application->reviewed_at || $application->decision_reason || $application->decision_notes)
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                    @if ($application->reviewed_at)
                                        <div class="summary-item">
                                            <p class="summary-label">Reviewed At</p>
                                            <p class="summary-value">{{ $application->reviewed_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                    @endif

                                    @if ($application->decision_reason)
                                        <div class="summary-item">
                                            <p class="summary-label">Decision Reason</p>
                                            <p class="summary-value">{{ $application->decision_reason }}</p>
                                        </div>
                                    @endif

                                    @if ($application->decision_notes)
                                        <div class="summary-item">
                                            <p class="summary-label">Decision Notes</p>
                                            <p class="summary-value">{{ $application->decision_notes }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($application->clearance)
                            <div class="final-clearance-card">
                                <div class="final-clearance-title-row">
                                    <div>
                                        <h2 class="review-panel-title">Generated Decision Output</h2>
                                        <p class="review-panel-subtitle">Decision output generated from the final application result. This is not an ownership transfer record.</p>
                                    </div>
                                    <span class="staff-badge {{ in_array($application->clearance->decision_status, ['released', 'approved'], true) ? 'staff-badge-green' : 'staff-badge-red' }}">
                                        {{ in_array($application->clearance->decision_status, ['released', 'approved'], true) ? 'Clearance Released' : 'Denied' }}
                                    </span>
                                </div>

                                <div class="mt-4 summary-grid">
                                    <div class="summary-item">
                                        <p class="summary-label">Clearance No.</p>
                                        <p class="summary-value">{{ $application->clearance->clearance_number }}</p>
                                    </div>
                                    <div class="summary-item">
                                        <p class="summary-label">Generated At</p>
                                        <p class="summary-value">{{ optional($application->clearance->generated_at)->format('M d, Y h:i A') ?? 'â€”' }}</p>
                                    </div>
                                </div>

                                <div class="final-clearance-actions">
                                    <a href="{{ route('staff.applications.clearance.show', $application) }}" class="staff-button staff-button-dark">
                                        <i class="fa-solid fa-print"></i>
                                        Print View
                                    </a>
                                    <a href="{{ route('staff.applications.clearance.pdf', $application) }}" class="staff-button staff-button-primary" target="_blank">
                                        <i class="fa-solid fa-file-pdf"></i>
                                        PDF Output
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="final-clearance-card">
                                <h2 class="review-panel-title">Generated Decision Output</h2>
                                <p class="review-panel-subtitle">No generated decision output is attached yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <section class="review-single-grid">
            <div class="review-panel">
                <div class="review-panel-header">
                    <div>
                        <h2 class="review-panel-title">Application Summary</h2>
                        <p class="review-panel-subtitle">
                            Main clearance application details for staff review and traceability.
                        </p>
                    </div>
                    <span class="staff-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="review-panel-body">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <p class="summary-label">Application Code</p>
                            <p class="summary-value">{{ $application->application_code }}</p>
                        </div>
                        <div class="summary-item">
                            <p class="summary-label">Status</p>
                            <p class="summary-value">{{ $statusLabel }}</p>
                        </div>
                        <div class="summary-item">
                            <p class="summary-label">Transferor</p>
                            <p class="summary-value">{{ $application->transferor_name }}</p>
                        </div>
                        <div class="summary-item">
                            <p class="summary-label">Transferee</p>
                            <p class="summary-value">{{ $application->transferee_name }}</p>
                        </div>
                        <div class="summary-item">
                            <p class="summary-label">Barangay</p>
                            <p class="summary-value">{{ $application->barangay ?? 'â€”' }}</p>
                        </div>
                        <div class="summary-item">
                            <p class="summary-label">Municipality</p>
                            <p class="summary-value">{{ $application->municipality ?? 'â€”' }}</p>
                        </div>

                        @if ($applicationAgriculturalStatusLabels->isNotEmpty())
                            <div class="summary-item">
                                <p class="summary-label">Linked Parcel Agricultural Status</p>
                                <div class="summary-value flex flex-wrap gap-2">
                                    @foreach ($applicationAgriculturalStatusLabels as $agriculturalStatusLabel)
                                        <span class="staff-badge staff-badge-slate">{{ $agriculturalStatusLabel }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>


        <section id="landowner-links" class="review-panel">
            <div class="review-panel-header">
                <div>
                    <h2 class="review-panel-title">Landowner Record Links</h2>
                    <p class="review-panel-subtitle">
                        Link each typed party name to an encoded Landowner Record before final decision checking.
                    </p>
                </div>
                <span class="staff-badge {{ ($application->transferor_landowner_id && $application->transferee_landowner_id) ? 'staff-badge-green' : 'staff-badge-amber' }}">
                    {{ ($application->transferor_landowner_id && $application->transferee_landowner_id) ? 'Ready for Decision Check' : 'Links Needed' }}
                </span>
            </div>

            <div class="review-panel-body">
                <form method="POST" action="{{ route('staff.applications.landowner-links.update', $application) }}">
                    @csrf
                    @method('PATCH')

                    <div class="landowner-link-grid">
                        <div class="landowner-link-card {{ $application->transferor_landowner_id ? 'linked' : 'unlinked' }}">
                            <div class="landowner-link-heading">
                                <div>
                                    <h3 class="landowner-link-title">Transferor Link</h3>
                                    <p class="landowner-link-copy">Select the matching record for the transferor.</p>
                                </div>
                                <span class="staff-badge {{ $application->transferor_landowner_id ? 'staff-badge-green' : 'staff-badge-amber' }}">
                                    {{ $application->transferor_landowner_id ? 'Linked' : 'Needs Link' }}
                                </span>
                            </div>

                            <div class="landowner-link-name">
                                <strong>Typed Application Name</strong>
                                <p class="landowner-link-name-value">{{ $application->transferor_name ?: 'â€”' }}</p>
                                @if ($application->transferorLandowner)
                                    <div class="landowner-linked-note">
                                        <i class="fa-solid fa-link"></i>
                                        Linked to {{ $application->transferorLandowner->full_name }} Â· ID {{ $application->transferorLandowner->id }}
                                    </div>
                                @endif
                            </div>

                            <div class="landowner-link-field">
                                <label for="transferor_landowner_id">Existing Landowner Record</label>
                                <select id="transferor_landowner_id" name="transferor_landowner_id" class="review-input" @disabled($isFinal)>
                                    <option value="">No linked landowner record</option>
                                    @foreach ($landownerOptions as $landowner)
                                        <option value="{{ $landowner->id }}" @selected((int) old('transferor_landowner_id', $application->transferor_landowner_id) === (int) $landowner->id)>
                                            {{ $landowner->full_name }} â€” {{ $landowner->municipality ?? 'No municipality' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="landowner-link-card {{ $application->transferee_landowner_id ? 'linked' : 'unlinked' }}">
                            <div class="landowner-link-heading">
                                <div>
                                    <h3 class="landowner-link-title">Transferee Link</h3>
                                    <p class="landowner-link-copy">Select or create the transferee record for review checks.</p>
                                </div>
                                <span class="staff-badge {{ $application->transferee_landowner_id ? 'staff-badge-green' : 'staff-badge-amber' }}">
                                    {{ $application->transferee_landowner_id ? 'Linked' : 'Needs Link' }}
                                </span>
                            </div>

                            <div class="landowner-link-name">
                                <strong>Typed Application Name</strong>
                                <p class="landowner-link-name-value">{{ $application->transferee_name ?: 'â€”' }}</p>
                                @if ($application->transfereeLandowner)
                                    <div class="landowner-linked-note">
                                        <i class="fa-solid fa-link"></i>
                                        Linked to {{ $application->transfereeLandowner->full_name }} Â· ID {{ $application->transfereeLandowner->id }}
                                    </div>
                                @endif
                            </div>

                            <div class="landowner-link-field">
                                <label for="transferee_landowner_id">Existing Landowner Record</label>
                                <select id="transferee_landowner_id" name="transferee_landowner_id" class="review-input" @disabled($isFinal)>
                                    <option value="">No linked landowner record</option>
                                    @foreach ($landownerOptions as $landowner)
                                        <option value="{{ $landowner->id }}" @selected((int) old('transferee_landowner_id', $application->transferee_landowner_id) === (int) $landowner->id)>
                                            {{ $landowner->full_name }} â€” {{ $landowner->municipality ?? 'No municipality' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="landowner-link-save-row">
                        <p class="landowner-link-save-note">
                            These links are for processing and auditability only; they do not transfer ownership or change registry records.
                        </p>

                        <button type="submit" class="staff-button staff-button-primary" @disabled($isFinal)>
                            <i class="fa-solid fa-link"></i>
                            Save Links
                        </button>
                    </div>
                </form>

                @if (! $isFinal)
                    <div class="landowner-create-grid">
                        @if (! $application->transferor_landowner_id)
                            <form method="POST" action="{{ route('staff.applications.landowner-records.create', $application) }}" class="landowner-create-card landowner-create-card-highlight">
                                @csrf
                                <input type="hidden" name="party" value="transferor">
                                <div>
                                    <h3 class="landowner-create-title">Create Transferor Record</h3>
                                    <p class="landowner-create-note">Use only if the transferor is missing from Landowner Records.</p>
                                </div>
                                <button type="submit" class="staff-button staff-button-light">
                                    <i class="fa-solid fa-user-plus"></i>
                                    Create & auto-link from â€œ{{ $application->transferor_name ?: 'Transferor Name' }}â€
                                </button>
                            </form>
                        @else
                            <div class="landowner-create-card landowner-create-card-disabled">
                                <div>
                                    <h3 class="landowner-create-title">Transferor Record Linked</h3>
                                    <p class="landowner-create-note">{{ $application->transferorLandowner?->full_name ?? 'Transferor' }} is already connected.</p>
                                </div>
                                <span class="staff-button staff-button-light">
                                    <i class="fa-solid fa-check"></i>
                                    No creation needed
                                </span>
                            </div>
                        @endif

                        @if (! $application->transferee_landowner_id)
                            <form method="POST" action="{{ route('staff.applications.landowner-records.create', $application) }}" class="landowner-create-card landowner-create-card-highlight">
                                @csrf
                                <input type="hidden" name="party" value="transferee">
                                <div>
                                    <h3 class="landowner-create-title">Create Transferee Record</h3>
                                    <p class="landowner-create-note">Create and auto-link the recipient record for review checks.</p>
                                </div>
                                <button type="submit" class="staff-button staff-button-light">
                                    <i class="fa-solid fa-user-plus"></i>
                                    Create & auto-link from â€œ{{ $application->transferee_name ?: 'Transferee Name' }}â€
                                </button>
                            </form>
                        @else
                            <div class="landowner-create-card landowner-create-card-disabled">
                                <div>
                                    <h3 class="landowner-create-title">Transferee Record Linked</h3>
                                    <p class="landowner-create-note">{{ $application->transfereeLandowner?->full_name ?? 'Transferee' }} is already connected.</p>
                                </div>
                                <span class="staff-button staff-button-light">
                                    <i class="fa-solid fa-check"></i>
                                    No creation needed
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <section class="review-panel">
            <div class="review-panel-body completion-card">
                <div>
                    <h2 class="review-panel-title">Checklist Completion</h2>
                    <p class="review-panel-subtitle">
                        {{ $uploadedCount }} / {{ $totalReq }} uploaded. Incomplete requirements can still be saved, but will be flagged during review.
                    </p>
                </div>
                <div class="completion-number">{{ $uploadedCount }} / {{ $totalReq }}</div>
            </div>
        </section>

        @foreach ($requirementGroups as $group)
            <section class="review-panel">
                <div class="review-panel-header">
                    <div>
                        <h2 class="review-panel-title">{{ $group['title'] }}</h2>
                        <p class="review-panel-subtitle">{{ $group['description'] }}</p>
                    </div>
                </div>

                <div class="review-panel-body requirements-section">
                    @foreach ($group['requirements'] as $req)
                        @php
                            $doc = $uploaded->get($req->id);
                            $isUploaded = ! is_null($doc);
                            $editPanelId = 'document-edit-panel-' . $req->id;
                            $documentExists = $isUploaded && $doc->file_path && \Illuminate\Support\Facades\Storage::exists($doc->file_path);
                            $documentMime = $documentExists ? (\Illuminate\Support\Facades\Storage::mimeType($doc->file_path) ?: null) : null;
                            $isImageDocument = $documentMime && str_starts_with((string) $documentMime, 'image/');
                            $isPdfDocument = $documentMime === 'application/pdf';
                            $documentViewUrl = $documentExists
                                ? route('staff.applications.documents.show', ['application' => $application->id, 'requiredDocument' => $req->id])
                                : null;
                        @endphp

                        <article id="required-document-{{ $req->id }}" class="requirement-card">
                            <div class="requirement-main">
                                <div class="requirement-heading">
                                    <div class="requirement-title-wrap">
                                        <span class="requirement-title-icon" aria-hidden="true">
                                            <i class="fa-solid fa-file-circle-check"></i>
                                        </span>

                                        <div>
                                            <p class="requirement-type-label">Document Requirement</p>

                                            <h3 class="requirement-title">
                                                {{ $req->name }}
                                                @if (! $req->is_mandatory)
                                                    <span class="staff-badge staff-badge-amber ml-2">If applicable</span>
                                                @endif
                                            </h3>

                                            <p class="requirement-note">
                                                Upload files and encode only the necessary reference details for review, indexing, monitoring, and auditability.
                                            </p>
                                        </div>
                                    </div>

                                    @if ($isUploaded)
                                        <span class="staff-badge staff-badge-green">Uploaded</span>
                                    @else
                                        <span class="staff-badge staff-badge-red">Not uploaded</span>
                                    @endif
                                </div>

                                @if ($isUploaded)
                                    <div class="document-status-panel uploaded">
                                        <p class="document-status-title">
                                            <i class="fa-solid fa-file-circle-check text-green-700"></i>
                                            Uploaded Document
                                        </p>
                                        <p class="document-status-copy">
                                            A file is already attached to this requirement. Use Edit to replace the file and update its indexing details, or Remove to delete the uploaded document record.
                                        </p>

                                        <div class="document-detail-grid mt-3">
                                            <div class="document-detail-item">
                                                <strong>Filename</strong>
                                                <div class="font-mono break-all mt-1">{{ $doc->original_filename }}</div>
                                            </div>

                                            <div class="document-detail-item">
                                                <strong>Annex Reference</strong>
                                                <div class="mt-1">{{ $doc->annex_reference ?: 'â€”' }}</div>
                                            </div>

                                            <div class="document-detail-item">
                                                <strong>Reference No.</strong>
                                                <div class="mt-1">{{ $doc->document_reference_number ?: 'â€”' }}</div>
                                            </div>

                                            <div class="document-detail-item">
                                                <strong>Remarks</strong>
                                                <div class="mt-1">{{ $doc->remarks ?: 'â€”' }}</div>
                                            </div>

                                        </div>

                                        @if ($documentExists)
                                            <div class="document-file-review">
                                                <a href="{{ $documentViewUrl }}"
                                                   target="_blank"
                                                   rel="noopener"
                                                   class="document-file-preview"
                                                   aria-label="Open uploaded document">
                                                    @if ($isImageDocument)
                                                        <img src="{{ $documentViewUrl }}" alt="Preview of {{ $doc->original_filename }}">
                                                    @else
                                                        <span class="document-file-icon" aria-hidden="true">
                                                            <i class="fa-solid {{ $isPdfDocument ? 'fa-file-pdf' : 'fa-file-lines' }}"></i>
                                                        </span>
                                                    @endif
                                                </a>

                                                <div class="document-file-info">
                                                    <div>
                                                        <p class="document-file-label">Staff File Review</p>
                                                        <p class="document-file-name">{{ $doc->original_filename }}</p>
                                                    </div>

                                                    <div class="document-file-meta">
                                                        <span class="document-file-chip">
                                                            <i class="fa-solid fa-eye"></i>
                                                            {{ $isImageDocument ? 'Image Preview' : ($isPdfDocument ? 'PDF Output' : 'Uploaded File') }}
                                                        </span>
                                                        @if ($documentMime)
                                                            <span class="document-file-chip">
                                                                <i class="fa-solid fa-file-shield"></i>
                                                                {{ $documentMime }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="document-file-actions">
                                                        <a href="{{ $documentViewUrl }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="staff-button staff-button-light">
                                                            <i class="fa-solid {{ $isImageDocument ? 'fa-image' : ($isPdfDocument ? 'fa-file-pdf' : 'fa-arrow-up-right-from-square') }}"></i>
                                                            {{ $isImageDocument ? 'View Image' : ($isPdfDocument ? 'Open PDF' : 'Open File') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="document-file-missing">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Uploaded file record found, but the stored file could not be located. Check storage/app and the file path before review.
                                            </div>
                                        @endif


                                        <div class="document-current-summary">
                                            <p class="document-current-summary-title">
                                                <i class="fa-solid fa-tags text-green-700 text-xs"></i>
                                                Current Document Indexing
                                            </p>

                                            @if ($doc->document_metadata)
                                                <div class="document-current-summary-list">
                                                @if (data_get($doc->document_metadata, 'title_number'))
                                                    <div><strong>Title No.:</strong> {{ data_get($doc->document_metadata, 'title_number') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'tax_declaration_number'))
                                                    <div><strong>Tax Declaration No.:</strong> {{ data_get($doc->document_metadata, 'tax_declaration_number') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'document_number'))
                                                    <div><strong>Document No.:</strong> {{ data_get($doc->document_metadata, 'document_number') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'issuing_office'))
                                                    <div><strong>Issuing Office:</strong> {{ data_get($doc->document_metadata, 'issuing_office') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'date_issued'))
                                                    <div><strong>Date Issued:</strong> {{ data_get($doc->document_metadata, 'date_issued') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'reference_lot_or_parcel'))
                                                    <div><strong>Reference Lot/Parcel:</strong> {{ data_get($doc->document_metadata, 'reference_lot_or_parcel') }}</div>
                                                @endif

                                                @if (data_get($doc->document_metadata, 'verification_notes'))
                                                    <div><strong>Verification Notes:</strong> {{ data_get($doc->document_metadata, 'verification_notes') }}</div>
                                                @endif
                                                </div>
                                            @else
                                                <div class="text-gray-500 text-xs">No document metadata has been encoded yet.</div>
                                            @endif

                                            @if ($doc->metadataEncoder || $doc->metadata_encoded_at)
                                                <div class="document-current-summary-meta">
                                                    Encoded by
                                                    <span class="font-semibold text-gray-700">{{ optional($doc->metadataEncoder)->name ?? 'Unknown user' }}</span>
                                                    @if ($doc->metadata_encoded_at)
                                                        on {{ $doc->metadata_encoded_at->format('M d, Y h:i A') }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        @if (! $isFinal)
                                            <div class="document-button-row">
                                                <button type="button"
                                                        class="staff-button staff-button-primary"
                                                        onclick="const panel = document.getElementById('{{ $editPanelId }}'); panel.hidden = ! panel.hidden; if (! panel.hidden) { panel.scrollIntoView({ block: 'nearest' }); }">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Edit
                                                </button>

                                                <form method="POST"
                                                      action="{{ route('staff.applications.documents.destroy', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                                                      class="document-remove-form"
                                                      data-preserve-scroll
                                                      onsubmit="return confirm('Remove this uploaded document? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="staff-button staff-button-danger">
                                                        <i class="fa-solid fa-trash"></i>
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>

                                            <div id="{{ $editPanelId }}" class="document-edit-panel" hidden>
                                                <div class="document-indexing-header">
                                                    <div>
                                                        <p class="document-edit-panel-title">Edit Uploaded Document</p>
                                                        <p class="document-edit-panel-copy">
                                                            Update the file or indexing details below. Choose a replacement file only when the uploaded file itself needs to be changed.
                                                        </p>
                                                    </div>
                                                    <span class="document-indexing-label">
                                                        <i class="fa-solid fa-database"></i>
                                                        Indexing Only
                                                    </span>
                                                </div>

                                                <form method="POST"
                                                      action="{{ route('staff.applications.documents.store', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                                                      enctype="multipart/form-data"
                                                      class="document-form-section"
                                                      data-preserve-scroll>
                                                    @csrf

                                                    <div class="file-input-wrap">
                                                        <label class="block mb-2">Replacement file (optional)</label>
                                                        <input type="file" name="file">
                                                        <p class="file-input-help">Leave this blank to keep the currently uploaded file and save only the reference/indexing changes.</p>
                                                    </div>

                                                    <div class="upload-grid">
                                                        <div>
                                                            <label class="block mb-1">Annex reference (optional)</label>
                                                            <input type="text"
                                                                   name="annex_reference"
                                                                   class="review-input"
                                                                   value="{{ old('annex_reference', $doc->annex_reference ?? '') }}">
                                                        </div>

                                                        <div>
                                                            <label class="block mb-1">Remarks (optional)</label>
                                                            <input type="text"
                                                                   name="remarks"
                                                                   class="review-input"
                                                                   value="{{ old('remarks', $doc->remarks ?? '') }}">
                                                        </div>
                                                    </div>

                                                    @include('staff.applications.partials.document-metadata-fields', [
                                                        'req' => $req,
                                                        'doc' => $doc,
                                                        'isFinal' => $isFinal,
                                                        'lockMsg' => $lockMsg,
                                                    ])

                                                    <div class="document-action-row">
                                                        <button type="submit" class="staff-button staff-button-primary">
                                                            <i class="fa-solid fa-floppy-disk"></i>
                                                            Save Edited Document
                                                        </button>

                                                        <button type="button"
                                                                class="staff-button staff-button-light"
                                                                onclick="document.getElementById('{{ $editPanelId }}').hidden = true;">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @else
                                            <div class="document-locked-note">
                                                {{ $lockMsg }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <form method="POST"
                                          action="{{ route('staff.applications.documents.store', ['application' => $application->id, 'requiredDocument' => $req->id]) }}"
                                          enctype="multipart/form-data"
                                          class="document-upload-panel"
                                          data-preserve-scroll>
                                        @csrf

                                        <div class="document-indexing-header">
                                            <div>
                                                <p class="document-form-title">
                                                    <i class="fa-solid fa-triangle-exclamation text-red-700"></i>
                                                    Upload Document
                                                </p>
                                                <p class="document-form-copy">
                                                    Upload the required file for staff review and traceability.
                                                </p>
                                            </div>
                                            <span class="document-indexing-label">
                                                <i class="fa-solid fa-database"></i>
                                                Indexing Only
                                            </span>
                                        </div>

                                        <div class="document-form-section">
                                            <div class="file-input-wrap">
                                                <label class="block mb-2">Choose file (required)</label>
                                                <input type="file"
                                                       name="file"
                                                       required
                                                       {{ $isFinal ? 'disabled' : '' }}
                                                       title="{{ $isFinal ? $lockMsg : '' }}">
                                                <p class="file-input-help">Accepted file is stored as an application document for staff review and indexing only.</p>
                                            </div>

                                            <div class="upload-grid">
                                                <div>
                                                    <label class="block mb-1">Annex reference (optional)</label>
                                                    <input type="text"
                                                           name="annex_reference"
                                                           class="review-input"
                                                           value="{{ old('annex_reference') }}"
                                                           {{ $isFinal ? 'disabled' : '' }}
                                                           title="{{ $isFinal ? $lockMsg : '' }}">
                                                </div>

                                                <div>
                                                    <label class="block mb-1">Remarks (optional)</label>
                                                    <input type="text"
                                                           name="remarks"
                                                           class="review-input"
                                                           value="{{ old('remarks') }}"
                                                           {{ $isFinal ? 'disabled' : '' }}
                                                           title="{{ $isFinal ? $lockMsg : '' }}">
                                                </div>
                                            </div>

                                            @include('staff.applications.partials.document-metadata-fields', [
                                                'req' => $req,
                                                'doc' => null,
                                                'isFinal' => $isFinal,
                                                'lockMsg' => $lockMsg,
                                            ])

                                            <div class="document-action-row">
                                                <button type="submit"
                                                        class="staff-button staff-button-dark"
                                                        title="{{ $isFinal ? $lockMsg : '' }}"
                                                        {{ $isFinal ? 'disabled' : '' }}
                                                        @if($isFinal) style="opacity:0.7; cursor:not-allowed;" @endif>
                                                    <i class="fa-solid fa-upload"></i>
                                                    Upload Document
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
        <section class="review-panel workflow-bottom-panel" id="workflow-actions-bottom">
            <div class="review-panel-header">
                <div>
                    <h2 class="review-panel-title">Workflow Actions</h2>
                    <p class="review-panel-subtitle">
                        Use this after reviewing the encoded details and uploaded requirements.
                    </p>
                </div>

                <span class="workflow-status-pill">
                    <i class="fa-solid fa-circle-info"></i>
                    Current Status: {{ $statusLabel }}
                </span>
            </div>

            <div class="review-panel-body workflow-box">
                @if ($isFinal)
                    <div class="review-note-box">
                        No workflow actions are available because this application is finalized.
                    </div>
                @elseif ($canAdvanceWorkflow || $canRelease || $canDeny)
                    <div class="workflow-decision-grid">
                        @if ($canAdvanceWorkflow)
                            <form method="POST" action="{{ route('staff.applications.submit', $application) }}" class="workflow-decision-card approve-card">
                                @csrf

                                <div class="workflow-decision-heading">
                                    <span class="workflow-action-icon" aria-hidden="true">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </span>

                                    <div>
                                        <p class="workflow-action-title">Advance to {{ $nextWorkflowStatusLabel }}</p>
                                        <p class="workflow-action-copy">
                                            Move this application to the next DAR office workflow stage. This does not approve, release, transfer ownership, or mutate registry records.
                                        </p>
                                    </div>
                                </div>

                                <div class="workflow-decision-actions">
                                    <div class="workflow-decision-note">
                                        Use this only after the current stage review is complete.
                                    </div>
                                </div>

                                <button type="submit" class="staff-button staff-button-primary">
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Advance Stage
                                </button>
                            </form>
                        @endif

                        @if ($canRelease)
                            <form method="POST" action="{{ route('staff.applications.approve', $application) }}" class="workflow-decision-card approve-card" data-decision-confirm="release">
                                @csrf

                                <div class="workflow-decision-heading">
                                    <span class="workflow-action-icon" aria-hidden="true">
                                        <i class="fa-solid fa-check"></i>
                                    </span>

                                    <div>
                                        <p class="workflow-action-title">Release clearance</p>
                                        <p class="workflow-action-copy">
                                            Generate and record the released clearance result. This does not automatically transfer land ownership or mutate registry records.
                                        </p>
                                    </div>
                                </div>

                                <div class="workflow-decision-actions">
                                    <div class="workflow-form-fields">
                                        <input type="text" name="decision_reason" placeholder="Reason / basis (optional)" class="review-input">
                                        <input type="text" name="decision_notes" placeholder="Internal notes (optional)" class="review-input">
                                    </div>

                                    <div class="workflow-decision-note">
                                        Use only after PARPO II review/signature and when the clearance is ready for release.
                                    </div>
                                </div>

                                <button type="submit" class="staff-button staff-button-primary">
                                    <i class="fa-solid fa-check"></i>
                                    Release Clearance
                                </button>
                            </form>
                        @endif

                        @if ($canDeny)
                            <form method="POST" action="{{ route('staff.applications.not_approved', $application) }}" class="workflow-decision-card not-approved-card" data-decision-confirm="deny">
                                @csrf

                                <div class="workflow-decision-heading">
                                    <span class="workflow-action-icon warning" aria-hidden="true">
                                        <i class="fa-solid fa-xmark"></i>
                                    </span>

                                    <div>
                                        <p class="workflow-action-title">Deny application</p>
                                        <p class="workflow-action-copy">
                                            Record a final denied decision and lock further editing or upload actions for audit integrity.
                                        </p>
                                    </div>
                                </div>

                                <div class="workflow-decision-actions">
                                    <div class="workflow-form-fields">
                                        <input type="text" name="decision_reason" placeholder="Denial reason / basis (required)" class="review-input" required>
                                        <input type="text" name="decision_notes" placeholder="Internal notes (optional)" class="review-input">
                                    </div>

                                    <div class="workflow-decision-note">
                                        This finalizes the application as Denied and preserves the record for monitoring and audit review.
                                    </div>
                                </div>

                                <button type="submit" class="staff-button staff-button-danger">
                                    <i class="fa-solid fa-xmark"></i>
                                    Mark as Denied
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <div class="review-note-box">
                        No workflow action is available for this status.
                    </div>
                @endif
            </div>
        </section>

        @if ($transfereeOwner)
            <section class="review-panel">
                <div class="review-panel-header">
                    <div>
                        <h2 class="review-panel-title">5-Hectare Validation (Assistive)</h2>
                        <p class="review-panel-subtitle">
                            This validation is based on encoded active landholding records and pending/current application areas only. It is assistive for staff review, not final legal authority.
                        </p>
                    </div>
                    @if ($exceedsFiveHectares)
                        <span class="staff-badge staff-badge-red">Limit Flagged</span>
                    @else
                        <span class="staff-badge staff-badge-green">Within Limit</span>
                    @endif
                </div>

                <div class="review-panel-body">
                    <div class="validation-grid">
                        <div class="validation-item"><strong>Transferee</strong>{{ $transfereeOwner->full_name }}</div>
                        <div class="validation-item"><strong>Current Active Hectares</strong>{{ number_format($currentApprovedTotal, 4) }} ha</div>
                        <div class="validation-item"><strong>Pending Incoming Total</strong>{{ number_format($pendingIncomingTotal, 4) }} ha</div>
                        <div class="validation-item"><strong>This Application Total</strong>{{ number_format($thisApplicationTotal, 4) }} ha</div>
                        <div class="validation-item"><strong>Projected Total</strong>{{ number_format($projectedTotal, 4) }} ha</div>
                    </div>

                    <div class="review-note-box mt-4">
                        @if ($exceedsFiveHectares)
                            Projected total exceeds the 5-hectare reference limit based on encoded records. Release is blocked until records are resolved or the application is marked Denied. This does not automatically decide legal ownership.
                        @else
                            Projected total is within the 5-hectare reference limit based on encoded system records.
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <section class="review-panel">
            <div class="review-panel-header">
                <div>
                    <h2 class="review-panel-title">Prior / Source Records</h2>
                    <p class="review-panel-subtitle">
                        Matched digitized source records and source packages related to this applicationâ€™s parcel, title, transferor, or transferee.
                        These records support review and traceability only. They do not automatically transfer land ownership or mutate Registry of Deeds records.
                    </p>
                </div>
            </div>

            <div class="review-panel-body space-y-6">
                @if ($matchedSourcePackages->count() > 0)
                    <div>
                        <h3 class="font-heading text-base font-bold text-gray-900 mb-3">Matched Source Packages</h3>
                        <div class="source-table-wrap">
                            <table class="staff-table">
                                <thead>
                                    <tr>
                                        <th>Package Code</th>
                                        <th>Status</th>
                                        <th>References</th>
                                        <th>Linked Parcel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matchedSourcePackages as $package)
                                        <tr>
                                            <td><strong>{{ $package->package_code }}</strong></td>
                                            <td>{{ $package->status_label }}</td>
                                            <td>
                                                @if ($package->title_number)<div><strong>Title:</strong> {{ $package->title_number }}</div>@endif
                                                @if ($package->parcel_code)<div><strong>Parcel Ref:</strong> {{ $package->parcel_code }}</div>@endif
                                                @if ($package->landholding_reference_number)<div><strong>Landholding:</strong> {{ $package->landholding_reference_number }}</div>@endif
                                                @if ($package->control_number)<div><strong>Clearance:</strong> {{ $package->control_number }}</div>@endif
                                            </td>
                                            <td>
                                                @if ($package->parcel)
                                                    <a href="{{ route('staff.records.parcels.show', $package->parcel) }}" class="staff-link">
                                                        {{ $package->parcel->parcel_code }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">Not linked</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('staff.source-record-packages.show', $package) }}" class="staff-button staff-button-light">
                                                    View Package
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($matchedSourceRecords->count() > 0)
                    <div>
                        <h3 class="font-heading text-base font-bold text-gray-900 mb-3">Matched Individual Source Records</h3>
                        <div class="source-table-wrap">
                            <table class="staff-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Origin</th>
                                        <th>References</th>
                                        <th>Source</th>
                                        <th>Linked Parcel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matchedSourceRecords as $record)
                                        <tr>
                                            <td><strong>{{ $record->record_type_label }}</strong></td>
                                            <td>
                                                <span class="staff-badge @if ($record->origin === 'encoded') staff-badge-blue @elseif ($record->origin === 'imported') staff-badge-amber @else staff-badge-slate @endif">
                                                    {{ $record->origin_label }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($record->title_number)<div><strong>Title:</strong> {{ $record->title_number }}</div>@endif
                                                @if ($record->parcel_code)<div><strong>Parcel Ref:</strong> {{ $record->parcel_code }}</div>@endif
                                                @if ($record->landholding_reference_number)<div><strong>Landholding:</strong> {{ $record->landholding_reference_number }}</div>@endif
                                                @if ($record->control_number)<div><strong>Clearance:</strong> {{ $record->control_number }}</div>@endif
                                                @if (! $record->title_number && ! $record->parcel_code && ! $record->landholding_reference_number && ! $record->control_number) â€” @endif
                                            </td>
                                            <td>
                                                <div>{{ $record->source_book }}</div>
                                                <div class="text-xs text-gray-500">Page: {{ $record->page_number ?? 'â€”' }}</div>
                                            </td>
                                            <td>
                                                @if ($record->parcel)
                                                    <a href="{{ route('staff.records.parcels.show', $record->parcel) }}" class="staff-link">
                                                        {{ $record->parcel->parcel_code }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">Not linked</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('staff.legacy-records.show', $record) }}" class="staff-button staff-button-light">
                                                    View Record
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($matchedSourcePackages->count() === 0 && $matchedSourceRecords->count() === 0)
                    <div class="review-note-box text-center">
                        No matching source records were found for this application.
                    </div>
                @endif
            </div>
        </section>

        <details class="review-panel timeline-collapsible">
            <summary>
                <div class="timeline-summary-row">
                    <div class="timeline-summary-left">
                        <span class="timeline-summary-icon" aria-hidden="true"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <div>
                            <h2 class="review-panel-title">Application Timeline / Status History</h2>
                            <p class="review-panel-subtitle">{{ $applicationTimeline->count() }} recorded action(s). Open this section only when you need to inspect the audit-based status trail.</p>
                        </div>
                    </div>
                    <span class="timeline-chevron" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
                </div>
            </summary>

            <div class="review-panel-body">
                <div class="review-note-box mb-4">
                    Timeline records are based on audit logs. They support traceability only and do not indicate automatic land ownership transfer or registry mutation.
                </div>
                @if ($applicationTimeline->isEmpty())
                    <div class="review-note-box text-center">No timeline records found yet.</div>
                @else
                    <div class="timeline-list">
                        @foreach ($applicationTimeline as $timelineEntry)
                            <article class="timeline-entry">
                                <div class="timeline-entry-header">
                                    <div>
                                        <p class="timeline-action">{{ ucwords(str_replace('_', ' ', $timelineEntry->action)) }}</p>
                                        <p class="timeline-meta">
                                            By:
                                            @if ($timelineEntry->actor)
                                                {{ $timelineEntry->actor->name }}
                                                <span class="text-gray-400">({{ $timelineEntry->actor->email }})</span>
                                            @else
                                                Unknown user
                                            @endif
                                        </p>
                                    </div>
                                    <div class="timeline-meta md:text-right">
                                        {{ $timelineEntry->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') ?? 'N/A' }}
                                    </div>
                                </div>

                                @if ($timelineEntry->auditable_type)
                                    <div class="timeline-meta mt-2">
                                        Related record:
                                        {{ class_basename($timelineEntry->auditable_type) }}
                                        @if ($timelineEntry->auditable_id)
                                            #{{ $timelineEntry->auditable_id }}
                                        @endif
                                    </div>
                                @endif

                                @if (! empty($timelineEntry->metadata))
                                    <details class="mt-3">
                                        <summary class="details-summary"><i class="fa-solid fa-caret-right"></i> View action details</summary>
                                        <pre class="details-pre">{{ json_encode($timelineEntry->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </details>
    </div>



        <div id="decision-confirm-modal" class="decision-modal-backdrop" aria-hidden="true">
            <div class="decision-modal-card" role="dialog" aria-modal="true" aria-labelledby="decision-confirm-title" aria-describedby="decision-confirm-copy">
                <div class="decision-modal-header">
                    <span id="decision-confirm-icon" class="decision-modal-icon" aria-hidden="true">
                        <i class="fa-solid fa-check"></i>
                    </span>
                    <div>
                        <h2 id="decision-confirm-title" class="decision-modal-title">Confirm final decision</h2>
                        <p id="decision-confirm-copy" class="decision-modal-copy">
                            This action will finalize the application record.
                        </p>
                    </div>
                </div>

                <div class="decision-modal-body">
                    <div id="decision-confirm-warning" class="decision-modal-warning">
                        Finalized applications lock further edits and document uploads for audit integrity.
                    </div>
                </div>

                <div class="decision-modal-actions">
                    <button type="button" class="staff-button staff-button-light" id="decision-confirm-cancel">
                        Cancel
                    </button>
                    <button type="button" class="staff-button staff-button-primary" id="decision-confirm-submit">
                        Confirm
                    </button>
                </div>
            </div>
        </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scrollKey = 'dar_ltcms_application_review_scroll';

            document.querySelectorAll('form[data-preserve-scroll]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const requirementCard = form.closest('.requirement-card');

                    sessionStorage.setItem(scrollKey, JSON.stringify({
                        y: window.scrollY,
                        requirementCardId: requirementCard ? requirementCard.id : null
                    }));
                });
            });

            const saved = sessionStorage.getItem(scrollKey);

            if (saved) {
                sessionStorage.removeItem(scrollKey);

                try {
                    const state = JSON.parse(saved);

                    requestAnimationFrame(function () {
                        if (state.requirementCardId) {
                            const requirementCard = document.getElementById(state.requirementCardId);

                            if (requirementCard) {
                                requirementCard.scrollIntoView({
                                    block: 'start',
                                    behavior: 'auto'
                                });
                                return;
                            }
                        }

                        if (typeof state.y === 'number') {
                            window.scrollTo(0, state.y);
                        }
                    });
                } catch (error) {
                    // Ignore corrupted scroll state.
                }
            }


            const decisionModal = document.getElementById('decision-confirm-modal');
            const decisionModalIcon = document.getElementById('decision-confirm-icon');
            const decisionModalTitle = document.getElementById('decision-confirm-title');
            const decisionModalCopy = document.getElementById('decision-confirm-copy');
            const decisionModalWarning = document.getElementById('decision-confirm-warning');
            const decisionModalSubmit = document.getElementById('decision-confirm-submit');
            const decisionModalCancel = document.getElementById('decision-confirm-cancel');
            let pendingDecisionForm = null;

            const decisionMessages = {
                release: {
                    icon: 'fa-check',
                    danger: false,
                    buttonClass: 'staff-button staff-button-primary',
                    buttonText: 'Release Clearance',
                    title: 'Release this clearance?',
                    copy: 'This will generate and record a released clearance result for this application.',
                    warning: 'Release records the clearance result only. It does not automatically transfer land ownership or mutate Registry of Deeds records.'
                },
                deny: {
                    icon: 'fa-xmark',
                    danger: true,
                    buttonClass: 'staff-button staff-button-danger',
                    buttonText: 'Mark as Denied',
                    title: 'Deny this application?',
                    copy: 'This will record a final denied decision for this application.',
                    warning: 'This finalizes the application and locks further editing or document uploads for audit integrity.'
                }
            };

            function openDecisionModal(form, type) {
                const config = decisionMessages[type] || decisionMessages.release;
                pendingDecisionForm = form;

                decisionModalIcon.className = 'decision-modal-icon' + (config.danger ? ' danger' : '');
                decisionModalIcon.innerHTML = '<i class="fa-solid ' + config.icon + '"></i>';
                decisionModalTitle.textContent = config.title;
                decisionModalCopy.textContent = config.copy;
                decisionModalWarning.textContent = config.warning;
                decisionModalSubmit.className = config.buttonClass;
                decisionModalSubmit.textContent = config.buttonText;

                decisionModal.classList.add('is-open');
                decisionModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                decisionModalSubmit.focus();
            }

            function closeDecisionModal() {
                decisionModal.classList.remove('is-open');
                decisionModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                pendingDecisionForm = null;
            }

            document.querySelectorAll('form[data-decision-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    openDecisionModal(form, form.getAttribute('data-decision-confirm'));
                });
            });

            decisionModalCancel.addEventListener('click', closeDecisionModal);

            decisionModal.addEventListener('click', function (event) {
                if (event.target === decisionModal) {
                    closeDecisionModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && decisionModal.classList.contains('is-open')) {
                    closeDecisionModal();
                }
            });

            decisionModalSubmit.addEventListener('click', function () {
                if (! pendingDecisionForm) {
                    return;
                }

                const form = pendingDecisionForm;
                pendingDecisionForm = null;
                decisionModal.classList.remove('is-open');
                decisionModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                form.submit();
            });
        });
    </script>
</x-staff-shell>

