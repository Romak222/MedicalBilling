# Repository Instructions

This project is a commercial pharmacy management system foundation. Treat safety, auditability, offline operation, and data correctness as product requirements.

## Working Rules

- Preserve user work. Do not reset or revert changes unless explicitly requested.
- Work phase by phase. The current phase is recorded in `PROGRESS.md`.
- Do not add medicine, purchase, inventory, or POS functionality during Phase 1.
- Do not create public default administrator passwords.
- Do not hardcode GST thresholds, legal thresholds, business data, medicine data, or state-specific compliance rules.
- Keep customer, patient, prescription, and regulated-drug data behind explicit access controls when those modules are implemented.
- Use migrations for schema changes.
- Use decimal columns for money and measured quantities; never use floating-point types for money.
- Use database transactions for sales, purchases, returns, stock, accounting, and audit-sensitive workflows.
- Inventory must be based on immutable stock movements, not only an editable quantity column.
- Finalized financial transactions should be reversed, not silently deleted.
- Keep online features separate from the offline core. Failed online work must go to an outbox and must not block billing.
- Avoid internet-hosted runtime assets. Bundle fonts, scripts, styles, and icons locally.

## Verification

Run these when relevant:

```powershell
composer test
npm run build
composer format
```

NativePHP commands require PHP 8.3+ in this project.
