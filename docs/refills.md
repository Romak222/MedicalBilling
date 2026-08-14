# Refills

Phase 10 adds prescription refill scheduling and repeat-dispense reminder tracking.

## Routes

- `/prescriptions/refills`
- `/prescriptions/refills/{prescriptionItem}`

These routes require first-run setup, login, and `prescriptions.view`.

## Implemented Tracking

Refill-aware prescription lines now store:

- refill interval in days
- reminder lead in days
- last dispensed date
- next refill due date

The refill tracker list shows:

- overdue lines
- due-soon lines
- pending first-fill lines
- upcoming lines
- completed refill lines

## Automatic Flow

1. Staff create or edit a prescription in `/prescriptions/create` or `/prescriptions/{prescription}/edit`.
2. Any medicine line can be given a refill interval and reminder lead.
3. When a finalized bill dispenses that prescription line, the system updates `last_dispensed_on`.
4. If the line still has remaining prescribed quantity, the system calculates `next_refill_due_on` from the billed dispense date plus the interval.
5. If a sales return reduces dispensed quantity, the refill tracker re-checks remaining billed history and re-calculates the due date.
6. If a bill is cancelled and no active dispensed quantity remains, the tracker clears the last-dispensed and next-due dates.
7. Staff review follow-up work from `/prescriptions/refills`.

The tracker does not create stock reservations, SMS jobs, or WhatsApp jobs yet. It is a local clinical-follow-up workspace driven from audited billing history.

## Demo Data

The demo seeders create:

- `RX-REFILL-DEMO-001` with product `RF-DEMO-001 / Refill Demo Capsule`
- finalized bill `SI-RF-DEMO-001` dated August 1, 2026
- refill interval `7` days and reminder lead `2` days

As of Thursday, August 13, 2026:

- `SI-RF-DEMO-001` produces an overdue refill because the next due date is August 8, 2026
- `SI-CM-DEMO-001` produces an upcoming controlled refill because the next due date is September 11, 2026

Seeder command:

```powershell
& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
```

## Boundary

This module does not yet implement:

- automated SMS, WhatsApp, or calling reminders
- refill claim billing or insurer follow-up
- doctor-approval loops for extra repeats
- masked-display or break-glass workflows
- regulator-specific repeat-dispense rules
