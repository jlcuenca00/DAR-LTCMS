// Account pages use native hidden state so panel visibility does not depend on Alpine.
function initAccountDetails() {
    document.querySelectorAll("[data-account-tabs]").forEach((root) => {
        const tabs = [...root.querySelectorAll("[data-account-tab]")];
        const panes = [...root.querySelectorAll("[data-account-pane]")];
        const select = (name, focus = false) => {
            if (!tabs.some((tab) => tab.dataset.accountTab === name)) return;
            tabs.forEach((tab) => {
                const selected = tab.dataset.accountTab === name;
                tab.setAttribute("aria-selected", String(selected));
                tab.tabIndex = selected ? 0 : -1;
                if (selected && focus) tab.focus();
            });
            panes.forEach((pane) => {
                pane.hidden = pane.dataset.accountPane !== name;
            });
        };
        tabs.forEach((tab, index) => {
            tab.addEventListener("click", () => select(tab.dataset.accountTab));
            tab.addEventListener("keydown", (event) => {
                let next;
                if (event.key === "ArrowRight")
                    next = (index + 1) % tabs.length;
                if (event.key === "ArrowLeft")
                    next = (index - 1 + tabs.length) % tabs.length;
                if (event.key === "Home") next = 0;
                if (event.key === "End") next = tabs.length - 1;
                if (next === undefined) return;
                event.preventDefault();
                select(tabs[next].dataset.accountTab, true);
            });
        });
        select(root.dataset.initialTab);

        const role = root.querySelector("#field-default-role");
        const landownerFields = root.querySelector("[data-landowner-fields]");
        if (role && landownerFields) {
            const updateRole = () => {
                landownerFields.hidden = role.value !== "landowner";
            };
            role.addEventListener("change", updateRole);
            updateRole();
        }
        root.querySelectorAll("[data-account-edit-form]").forEach((form) => {
            const snapshot = () =>
                JSON.stringify([...new FormData(form).entries()]);
            const initial = snapshot();
            const unsaved = form.querySelector("[data-account-unsaved]");
            const update = () => {
                if (unsaved) unsaved.hidden = snapshot() === initial;
            };
            form.addEventListener("input", update);
            form.addEventListener("change", update);
        });
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAccountDetails, {
        once: true,
    });
} else {
    initAccountDetails();
}
