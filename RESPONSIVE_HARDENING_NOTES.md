# Responsive Hardening Notes

This branch consolidates DAR-LTCMS responsive behavior into one screen-only responsive contract.

Scope:
- Staff, Landowner, and Geodetic portal navigation at 1100px and below
- 320px reflow and touch-target hardening
- dynamic viewport and safe-area handling
- local scrolling for dense two-dimensional tables only
- responsive parcel-map sizing and controls
- application review overlays, workflow controls, and requirement navigation
- responsive regression tests and Playwright browser matrix

Unchanged:
- application workflow semantics and final-decision rules
- role permissions and record ownership checks
- landowner/parcel/landholding business logic
- clearance generation semantics
- database schema and migrations
- print/PDF layouts
- automatic ownership or registry mutation remains outside system scope
