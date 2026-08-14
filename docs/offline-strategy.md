# Offline Strategy

## Offline Core

The following must work without internet:

- Login
- Billing
- Purchases and returns
- Stock and batch management
- Customer, patient, supplier, doctor records
- Prescription storage
- Cash and account management
- GST reports
- Business reports
- Barcode scanning
- Printing
- Backup and restore

## Online Modules

The following are optional and must be isolated from the core:

- WhatsApp and SMS
- GST e-invoice and IRN
- Cloud backup
- Online catalogue updates
- Distributor ERP connections
- Online payments
- Remote dashboard
- Multi-branch cloud synchronization

## Outbox Pattern

Online work should be recorded as durable `outbox_messages` inside the same local database transaction as the source action. A worker can retry messages when connectivity returns.

Rules:

- Outbox retries are idempotent.
- Failed online requests never roll back local billing.
- Retry state and failure reason are visible to authorized users.
- Remote identifiers are stored after successful sync.

## Local Data

Single-computer installations use SQLite in the application data directory when packaged. Development uses `database/database.sqlite`.

LAN installations use MySQL or MariaDB on one local server computer. Shared SQLite over a network drive is forbidden.

## Backup

Backups are local-first:

- Manual backup
- Automatic daily backup
- Backup before updates
- Configurable backup location
- Restore validation
- Future encrypted and cloud backup options
