# Controlled Medicines

Phase 10 adds a controlled-medicine register foundation driven directly from audited billing events.

## Routes

- `/controlled-medicines`
- `/controlled-medicines/{entry}`

These routes require first-run setup, login, and `controlled_medicines.view`.

## Implemented Records

- `controlled_medicine_register_entries`

Each entry stores:

- controlled product and batch linkage
- linked customer, patient, doctor, prescription, and prescription line when available
- linked sales invoice and sales return records when available
- event date
- quantity effect
- product, batch, patient, doctor, prescription, invoice, and return snapshot values
- generated notes

## Automatic Flow

1. A product is marked `controlled_medicine` in the catalogue.
2. Billing requires prescription linkage for that product.
3. When the bill is finalized, the system writes a controlled-medicine register entry with a positive quantity.
4. If the bill is cancelled, the system writes a reversal entry with a negative quantity.
5. If a sales return is finalized, the system writes a reversal entry with a negative quantity.
6. Staff can review the immutable register from `/controlled-medicines`.

The register has no create, edit, or delete screen. It is generated from billing events so the compliance trail stays aligned with the sales trail.

## Demo Data

The demo seeders now create:

- controlled product `CM-DEMO-001 / Controlled Demo Tablet`
- batch `CMB-DEMO-001`
- finalized bill `SI-CM-DEMO-001`
- linked controlled-medicine register entry
- refill-tracked controlled prescription line with next due date after the seeded August 12, 2026 dispense

Seeder command:

```powershell
& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
```

## Boundary

This module does not yet implement:

- statutory export formats or legal print layouts
- break-glass or masked-display workflows
- controlled purchase registers
- regulator-specific reporting packs

Repeat-dispense reminder tracking is documented separately in `docs/refills.md`.
