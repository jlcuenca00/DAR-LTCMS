# Security & Auditability

DAR-LTCMS is designed around role-based access, data integrity, and traceability.

## Role-based access

Users receive only the functions required for their authorized role.

- Landowners are restricted to their own linked information.
- Geodetic personnel have limited parcel and verification access.
- Staff functions are restricted according to authorized administrative responsibilities.

## Final decision protection

Current **Approved** applications are final and protected against substantive changes and document uploads. Older Not Approved / Denied records are historical finalized records and remain read-only. Approved core application data is also guarded at the model level; only the authorized forward release lifecycle may be recorded afterward.

## Audit trails

Important actions are recorded with information such as:

- the acting user;
- action type;
- affected record;
- timestamp; and
- meaningful change details when applicable.

These records support accountability and administrative traceability. Audit Logs defaults to **Activity**, which shows record/workflow actions. **Login History** separately shows authentication events and has its own filtered print view.

## Sensitive information

The public documentation does not publish passwords, API keys, server credentials, database credentials, internal security configuration, or other secrets.

## Claims and limits

These controls describe implemented behavior, not a certification or a guarantee that every possible security issue has been eliminated. The final release process includes fresh operational checks and explicitly deferred SSH host-trust hardening after the defense-finished instruction.
