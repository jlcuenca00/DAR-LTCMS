# DAR-LTCMS Responsive Design Guidelines

This checklist applies to interactive DAR-LTCMS web views for DAR Staff, Landowners, Geodetic Personnel, authentication, profile, notifications, and the public landing page. Print/PDF clearance forms are intentionally excluded because they follow fixed document layouts.

## Project rules

- Design from narrow content outward. Add a breakpoint when the content needs one rather than targeting a specific phone or device model.
- Every normal vertically scrolling view must remain usable at a 320 CSS pixel viewport without page-level horizontal scrolling or loss of functionality.
- Maintain at least **16px horizontal page gutter on phone layouts**. Cards may have additional internal padding.
- Use flexible widths (`width: 100%`, `max-width`, `minmax(0, 1fr)`) and set `min-width: 0` on grid/flex children that contain long record values.
- Reflow multi-column content to fewer columns and eventually one column when the content becomes crowded.
- Use a **44px DAR-LTCMS project target size** for primary buttons, form controls, navigation controls, and other common touch interactions on phone/touch layouts. WCAG 2.2 requires at least 24 by 24 CSS pixels or sufficient spacing; the project intentionally uses a larger ergonomic target where practical.
- Form fields use at least 16px text on phones to remain readable and avoid common mobile browser input zoom behavior.
- Images, SVGs, videos, and canvases must not exceed their containers.
- Long parcel codes, names, references, statuses, and filenames must wrap or truncate intentionally; they must never force the whole page wider.
- Tables containing ordinary records become labeled mobile cards. Keep semantic table markup on desktop.
- Two-dimensional content that genuinely requires spatial interaction, especially parcel maps and exceptional complex tables, may scroll/pan inside its own bounded region. The **page itself** must still reflow.
- Sidebars become compact mobile portal headers with explicit menu toggles. Do not render the entire desktop sidebar above the content on phones.
- Notification and account dropdowns must stay within the visual viewport.
- Honor `prefers-reduced-motion` for responsive interactions and transitions.
- Responsive CSS must be screen-only. Do not modify official print/PDF layout behavior through these rules.

## Required manual viewport checks

Before merging a major UI change, check the affected view at:

- **320px** — minimum reflow target
- **360px** — small Android-style viewport
- **390px** — common modern phone width
- **430px** — larger phone
- **768px** — tablet/narrow browser
- **1024px** — tablet/compact desktop
- **1280px+** — normal desktop

Also test browser zoom/reflow where practical, keyboard focus, long realistic record values, empty states, validation errors, open dropdowns, and pages containing maps or tables.

## Review checklist

- [ ] No page-level horizontal scrollbar at 320px for normal content.
- [ ] Phone content has at least 16px left/right breathing room.
- [ ] Navigation can be opened, closed, escaped, and used by keyboard.
- [ ] Important buttons and fields remain easy to tap.
- [ ] Forms reflow to one column when necessary.
- [ ] Labels remain associated with their fields.
- [ ] Cards and panels do not overflow the viewport.
- [ ] Tables become readable cards or are intentionally treated as two-dimensional exceptions.
- [ ] Maps fit inside the viewport and retain pan/zoom functionality.
- [ ] Account and notification menus stay onscreen.
- [ ] Status badges and long text do not cause horizontal overflow.
- [ ] Empty/error/success states remain readable.
- [ ] No role, permission, application workflow, clearance, ownership, or audit behavior changed as part of a responsive-only patch.

## Standards basis

The project follows the responsive design principles documented by web.dev and MDN: flexible layouts, content-driven breakpoints, and mobile-first reflow. It also uses WCAG 2.2 Reflow as the minimum accessibility baseline: vertically scrolling content should work at a width equivalent to 320 CSS pixels without two-dimensional page scrolling, except content whose meaning genuinely requires two dimensions such as maps or certain data tables. WCAG 2.2 Target Size (Minimum) is also considered for touch and pointer controls.
