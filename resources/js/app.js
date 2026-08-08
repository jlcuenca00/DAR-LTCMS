import './bootstrap';
import '../css/responsive.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// DAR-LTCMS Patch 1: account menu, tooltips, and live password validation
function initDarLtcmsPatchOneUi() {
    document.querySelectorAll('button[aria-label], a[aria-label], summary[aria-label]').forEach((control) => {
        if (!control.hasAttribute('title')) {
            control.setAttribute('title', control.getAttribute('aria-label'));
        }
    });

    document.querySelectorAll('[data-account-menu]').forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            document.querySelectorAll('[data-account-menu][open]').forEach((other) => {
                if (other !== menu) other.removeAttribute('open');
            });
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-account-menu][open]').forEach((menu) => menu.removeAttribute('open'));
    });

    document.querySelectorAll('[data-password-checklist]').forEach((checklist) => {
        const password = document.getElementById(checklist.dataset.passwordInput || '');
        const confirmation = checklist.dataset.passwordConfirmation
            ? document.getElementById(checklist.dataset.passwordConfirmation)
            : null;

        if (!password) return;

        const tests = {
            length: (value) => value.length >= 8,
            lower: (value) => /[a-z]/.test(value),
            upper: (value) => /[A-Z]/.test(value),
            number: (value) => /\d/.test(value),
            symbol: (value) => /[^A-Za-z0-9]/.test(value),
            match: (value) => Boolean(value) && confirmation && value === confirmation.value,
        };

        const update = () => {
            const value = password.value || '';
            checklist.querySelectorAll('[data-password-rule]').forEach((rule) => {
                const key = rule.dataset.passwordRule;
                const valid = Boolean(tests[key]?.(value));
                rule.classList.toggle('is-valid', valid);
                const icon = rule.querySelector('.password-rule-icon');
                if (icon) icon.textContent = valid ? '✓' : '○';
            });
        };

        password.addEventListener('input', update);
        confirmation?.addEventListener('input', update);
        update();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarLtcmsPatchOneUi, { once: true });
} else {
    initDarLtcmsPatchOneUi();
}

// DAR-LTCMS Phase 1 visual fix: exact modal profile photo cropper
function initDarLtcmsProfileCropper() {
    document.querySelectorAll('[data-profile-photo-editor]').forEach((editor) => {
        const chooseButton = editor.querySelector('[data-profile-photo-choose]');
        const fileInput = editor.querySelector('[data-profile-photo-input]');
        const currentImage = editor.querySelector('[data-profile-photo-current-image]');
        const emptyState = editor.querySelector('[data-profile-photo-empty]');
        const modal = editor.querySelector('[data-profile-crop-modal]');
        const stage = editor.querySelector('[data-profile-crop-stage]');
        const cropImage = editor.querySelector('[data-profile-crop-image]');
        const zoomInput = editor.querySelector('[data-profile-crop-zoom]');
        const zoomOutput = editor.querySelector('[data-profile-crop-zoom-output]');
        const saveButton = editor.querySelector('[data-profile-crop-save]');
        const cancelButtons = editor.querySelectorAll('[data-profile-crop-cancel]');

        if (!chooseButton || !fileInput || !modal || !stage || !cropImage || !zoomInput || !saveButton) {
            return;
        }

        let sourceImage = null;
        let sourceUrl = null;
        let previewUrl = null;
        let baseScale = 1;
        let zoom = 1;
        let offsetX = 0;
        let offsetY = 0;
        let pointerId = null;
        let dragStartX = 0;
        let dragStartY = 0;
        let dragStartOffsetX = 0;
        let dragStartOffsetY = 0;

        const stageSize = () => Math.max(1, stage.getBoundingClientRect().width);

        const clampOffsets = () => {
            if (!sourceImage) return;

            const size = stageSize();
            const scale = baseScale * zoom;
            const renderedWidth = sourceImage.naturalWidth * scale;
            const renderedHeight = sourceImage.naturalHeight * scale;
            const maxOffsetX = Math.max(0, (renderedWidth - size) / 2);
            const maxOffsetY = Math.max(0, (renderedHeight - size) / 2);

            offsetX = Math.max(-maxOffsetX, Math.min(maxOffsetX, offsetX));
            offsetY = Math.max(-maxOffsetY, Math.min(maxOffsetY, offsetY));
        };

        const renderCrop = () => {
            if (!sourceImage) return;

            const size = stageSize();
            const scale = baseScale * zoom;
            const renderedWidth = sourceImage.naturalWidth * scale;
            const renderedHeight = sourceImage.naturalHeight * scale;

            clampOffsets();

            cropImage.style.width = `${renderedWidth}px`;
            cropImage.style.height = `${renderedHeight}px`;
            cropImage.style.left = `${(size - renderedWidth) / 2 + offsetX}px`;
            cropImage.style.top = `${(size - renderedHeight) / 2 + offsetY}px`;

            if (zoomOutput) {
                zoomOutput.textContent = `${Math.round(zoom * 100)}%`;
            }
        };

        const resetCrop = () => {
            if (!sourceImage) return;

            const size = stageSize();
            baseScale = Math.max(
                size / sourceImage.naturalWidth,
                size / sourceImage.naturalHeight
            );

            zoom = 1;
            offsetX = 0;
            offsetY = 0;
            zoomInput.value = '1';
            renderCrop();
        };

        const closeModal = (clearSelection = false) => {
            modal.setAttribute('hidden', '');
            document.body.classList.remove('profile-crop-open');

            if (clearSelection) {
                fileInput.value = '';
            }

            pointerId = null;
        };

        const openModalForFile = (file) => {
            if (sourceUrl) {
                URL.revokeObjectURL(sourceUrl);
            }

            sourceUrl = URL.createObjectURL(file);
            modal.removeAttribute('hidden');
            document.body.classList.add('profile-crop-open');

            const image = new Image();

            image.onload = () => {
                sourceImage = image;
                cropImage.src = sourceUrl;

                requestAnimationFrame(() => {
                    resetCrop();
                    saveButton.focus();
                });
            };

            image.onerror = () => {
                sourceImage = null;
                closeModal(true);
            };

            image.src = sourceUrl;
        };

        chooseButton.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];

            if (!file) {
                return;
            }

            openModalForFile(file);
        });

        zoomInput.addEventListener('input', () => {
            zoom = Number.parseFloat(zoomInput.value || '1') || 1;
            renderCrop();
        });

        stage.addEventListener('pointerdown', (event) => {
            if (!sourceImage) return;

            pointerId = event.pointerId;
            stage.setPointerCapture(pointerId);
            dragStartX = event.clientX;
            dragStartY = event.clientY;
            dragStartOffsetX = offsetX;
            dragStartOffsetY = offsetY;
            stage.classList.add('is-dragging');
        });

        stage.addEventListener('pointermove', (event) => {
            if (pointerId !== event.pointerId) return;

            offsetX = dragStartOffsetX + (event.clientX - dragStartX);
            offsetY = dragStartOffsetY + (event.clientY - dragStartY);
            renderCrop();
        });

        const endDrag = (event) => {
            if (pointerId !== event.pointerId) return;

            pointerId = null;
            stage.classList.remove('is-dragging');

            if (stage.hasPointerCapture(event.pointerId)) {
                stage.releasePointerCapture(event.pointerId);
            }
        };

        stage.addEventListener('pointerup', endDrag);
        stage.addEventListener('pointercancel', endDrag);

        cancelButtons.forEach((button) => {
            button.addEventListener('click', () => closeModal(true));
        });

        saveButton.addEventListener('click', () => {
            if (!sourceImage || typeof DataTransfer === 'undefined') {
                closeModal(false);
                return;
            }

            const size = stageSize();
            const scale = baseScale * zoom;
            const renderedWidth = sourceImage.naturalWidth * scale;
            const renderedHeight = sourceImage.naturalHeight * scale;

            clampOffsets();

            const left = (size - renderedWidth) / 2 + offsetX;
            const top = (size - renderedHeight) / 2 + offsetY;
            const outputSize = 512;
            const outputScale = outputSize / size;

            const canvas = document.createElement('canvas');
            canvas.width = outputSize;
            canvas.height = outputSize;

            const context = canvas.getContext('2d');

            if (!context) {
                closeModal(false);
                return;
            }

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, outputSize, outputSize);
            context.drawImage(
                sourceImage,
                left * outputScale,
                top * outputScale,
                renderedWidth * outputScale,
                renderedHeight * outputScale
            );

            canvas.toBlob((blob) => {
                if (!blob) {
                    closeModal(false);
                    return;
                }

                const selectedFile = fileInput.files?.[0];
                const originalName = selectedFile?.name?.replace(/\.[^.]+$/, '') || 'profile-photo';
                const croppedFile = new File(
                    [blob],
                    `${originalName}-cropped.jpg`,
                    {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    }
                );

                const transfer = new DataTransfer();
                transfer.items.add(croppedFile);
                fileInput.files = transfer.files;

                if (previewUrl) {
                    URL.revokeObjectURL(previewUrl);
                }

                previewUrl = URL.createObjectURL(blob);

                if (currentImage) {
                    currentImage.src = previewUrl;
                    currentImage.hidden = false;
                }

                if (emptyState) {
                    emptyState.hidden = true;
                }

                closeModal(false);
            }, 'image/jpeg', 0.92);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
                closeModal(true);
            }
        });

        window.addEventListener('resize', () => {
            if (!modal.hasAttribute('hidden') && sourceImage) {
                resetCrop();
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarLtcmsProfileCropper, { once: true });
} else {
    initDarLtcmsProfileCropper();
}

// DAR-LTCMS Phase 3: compact staff navigation for tablets and phones.
function initDarLtcmsResponsiveStaffNavigation() {
    document.querySelectorAll('.staff-sidebar').forEach((sidebar) => {
        const brand = sidebar.querySelector('.staff-brand');
        const navSection = sidebar.querySelector('.staff-side-section');

        if (!brand || !navSection || brand.querySelector('.staff-mobile-nav-toggle')) {
            return;
        }

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'staff-mobile-nav-toggle';
        toggle.setAttribute('aria-label', 'Open staff navigation');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
        brand.appendChild(toggle);

        const setOpen = (open) => {
            sidebar.classList.toggle('is-mobile-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close staff navigation' : 'Open staff navigation');
            toggle.innerHTML = open
                ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
                : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
        };

        toggle.addEventListener('click', () => {
            setOpen(!sidebar.classList.contains('is-mobile-open'));
        });

        navSection.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('is-mobile-open')) {
                setOpen(false);
                toggle.focus();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900 && sidebar.classList.contains('is-mobile-open')) {
                setOpen(false);
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarLtcmsResponsiveStaffNavigation, { once: true });
} else {
    initDarLtcmsResponsiveStaffNavigation();
}
