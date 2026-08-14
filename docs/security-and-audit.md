# Security and Audit

## Authentication

The app uses local Laravel session authentication. First-run setup creates the owner account from installer-entered credentials, signs that user in, and does not ship a known default password.

Phase 4 adds:

- Login route: `/login`
- Logout route: `/logout`
- Protected dashboard/status routes after setup completion
- Login success/failure records in `login_events`

## Authorization

Use roles and configurable permissions for sensitive actions:

- View cost and profit
- Change prices
- Apply manual discounts
- Cancel invoices
- Process returns
- Modify stock
- Backdate transactions
- Access accounts
- Restore backup
- Manage users and settings

Owner approval PIN and cashier PIN can be added later, but passwords must remain hashed using Laravel's hashing facilities.

Phase 4 creates the system owner role and foundation permissions in `roles`, `permissions`, `role_user`, and `permission_role`. The owner role currently receives permissions for status viewing, setup/settings management, user and role management, audit viewing, and sensitive-action approval.

Phase 5 adds catalogue permissions for viewing catalogue records, managing catalogue records, and managing configurable tax-rate records.

Phase 6 adds supplier permissions for viewing and managing supplier and distributor directory records.

Phase 8 adds customer permissions for viewing and managing customer records.

Phase 8 also adds patient permissions for viewing and managing patient records with allergy and medical-note fields.

Phase 9 adds doctor permissions for viewing and managing doctor profiles and registration-linked records.

Phase 9 also adds prescription permissions for viewing and managing prescriptions, attachments, and linked dispensing records.

Phase 10 adds `controlled_medicines.view` for reviewing the controlled-medicine register.

Phase 6 also adds purchase permissions for viewing and managing purchase order foundation records.

Phase 7 adds inventory permissions for viewing batches and managing audited stock receiving.

Phase 8 adds sales permissions for viewing and managing POS billing and return records.

## Audit Records

Audit records must be immutable through normal screens.

Audit events should cover:

- Login and failed login
- Permission changes
- Price changes
- Discount approvals
- Invoice cancellation
- Stock adjustment
- Backup and restore
- Sensitive patient or prescription access
- Configuration changes

Sensitive change logs should include before-and-after values where safe to retain.

Phase 4 writes `audit_events` for setup completion, successful login, failed login, and logout. Phase 5 adds product create, update, deactivate, and restore audit events. Phase 6 adds supplier create, update, deactivate, and restore audit events plus purchase order create, update, sent, cancelled, and reopened events. Phase 7 adds purchase invoice create, update, cancelled, and finalized audit events. Phase 8 adds customer create, update, deactivate, and restore events, patient create, update, deactivate, and restore events, sales invoice create and cancelled events, and sales return create events. Phase 9 adds doctor create, update, deactivate, and restore events plus prescription create, update, archive, and restore events.

## Health and Personal Data

Patient, doctor, prescription, allergy, medical note, and controlled-medicine register data require stricter access controls and audit logging. Patient records are currently kept behind dedicated `patients.view` and `patients.manage` permissions. Doctor and prescription records are kept behind `doctors.view`, `doctors.manage`, `prescriptions.view`, and `prescriptions.manage`. Controlled-medicine register review is kept behind `controlled_medicines.view`. Export, retention, masked display, and break-glass access workflows must still be designed before production release.

## Secrets

Do not commit `.env`, credentials, API keys, signing certificates, or customer database files.

Native desktop builds should use secure local secret storage where possible. Signed updates are planned for the production release phase.

## Compliance

The software can support recordkeeping, warnings, and audit trails, but it must not claim to guarantee legal compliance by itself. Indian and state regulations must be reviewed before production release.
