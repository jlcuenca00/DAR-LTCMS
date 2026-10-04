/* Shared lifecycle for custom overlays. Existing callers own visibility and actions. */
let activeDialog = null;
const visible = node => node instanceof HTMLElement && node.isConnected
    && !node.closest('[inert], [hidden]') && node.getClientRects().length > 0
    && getComputedStyle(node).visibility !== 'hidden';
const focusable = modal => Array.from(modal.querySelectorAll(
    'a[href], button, input:not([type="hidden"]), select, textarea, summary, [tabindex]'
)).filter(node => visible(node) && !node.matches(':disabled') && node.tabIndex >= 0);

function close(modal, { restoreFocus = true } = {}) {
    if (!activeDialog || activeDialog.modal !== modal) return;
    const state = activeDialog;
    activeDialog = null;
    state.background.forEach(([node, wasInert]) => { node.inert = wasInert; });
    if (state.addedTabindex) modal.removeAttribute('tabindex');
    if (restoreFocus) {
        const target = [state.returnFocus, ...state.fallbacks].find(visible);
        target?.focus();
    }
}

function open(modal, { initialFocus, returnFocus = document.activeElement, fallbacks = [] } = {}) {
    if (!modal || activeDialog?.modal === modal) return;
    if (activeDialog) close(activeDialog.modal, { restoreFocus: false });
    const background = [];
    // Walk ancestors so nested cropper overlays and body portals both work.
    let branch = modal;
    while (branch.parentElement) {
        Array.from(branch.parentElement.children).forEach(node => {
            if (node === branch || !(node instanceof HTMLElement) || node.matches('script, style, link')) return;
            background.push([node, node.inert]);
            node.inert = true;
        });
        if (branch.parentElement === document.body) break;
        branch = branch.parentElement;
    }
    const addedTabindex = !modal.hasAttribute('tabindex');
    if (addedTabindex) modal.tabIndex = -1;
    activeDialog = { modal, returnFocus, fallbacks, background, addedTabindex };
    (visible(initialFocus) ? initialFocus : focusable(modal)[0] || modal).focus();
}

document.addEventListener('keydown', event => {
    if (event.key !== 'Tab' || !activeDialog) return;
    const { modal } = activeDialog;
    const controls = focusable(modal);
    const first = controls[0] || modal;
    const last = controls.at(-1) || modal;
    if (!modal.contains(document.activeElement) || document.activeElement === modal || controls.length === 0
        || (event.shiftKey && document.activeElement === first)
        || (!event.shiftKey && document.activeElement === last)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
}, true);

document.addEventListener('focusin', event => {
    if (activeDialog && !activeDialog.modal.contains(event.target)) {
        (focusable(activeDialog.modal)[0] || activeDialog.modal).focus();
    }
}, true);

window.DarDialogFocus = { open, close };
export { open, close };
