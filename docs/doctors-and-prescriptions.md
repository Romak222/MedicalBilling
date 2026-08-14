# Doctors and Prescriptions

Phase 9 adds doctor master records, prescription capture, and prescription-linked dispensing foundation.

## Routes

- `/doctors`
- `/doctors/create`
- `/doctors/{doctor}`
- `/doctors/{doctor}/edit`
- `/prescriptions`
- `/prescriptions/create`
- `/prescriptions/refills`
- `/prescriptions/refills/{prescriptionItem}`
- `/prescriptions/{prescription}`
- `/prescriptions/{prescription}/edit`
- `/prescriptions/{prescription}/attachment`

Doctor routes require first-run setup, login, and `doctors.view`. Creating, editing, deactivating, and restoring doctors requires `doctors.manage`.

Prescription routes require first-run setup, login, and `prescriptions.view`. Creating, editing, archiving, and restoring prescriptions requires `prescriptions.manage`.

## Implemented Records

- `doctors`
- `prescriptions`
- `prescription_items`

Doctors store:

- doctor name
- registration number
- specialization
- clinic name
- phone, alternate phone, and email
- address fields
- notes
- active/deactivated state

Prescriptions store:

- prescription number
- linked patient and optional linked doctor
- patient and doctor snapshot names
- patient phone snapshot
- prescription date and optional valid-until date
- status: `open`, `partial`, or `dispensed`
- local attachment metadata
- notes and pharmacist notes
- active/archived state

Prescription items store:

- optional linked product
- medicine and unit snapshot values
- dosage instructions
- prescribed quantity
- dispensed quantity
- refill interval in days
- reminder lead in days
- last dispensed date
- next refill due date
- line notes

## Workflow

1. Create or maintain a doctor record in `/doctors`.
2. Link that doctor as the patient's primary doctor from `/patients/create` or `/patients/{patient}/edit`.
3. Create a prescription in `/prescriptions/create`.
4. Select the patient. The form can auto-fill the linked doctor.
5. Upload an image or file attachment when needed.
6. Add one or more medicine lines with dosage instructions and prescribed quantities.
7. Optionally set a refill interval and reminder lead on any prescription line that should appear in the refill tracker.
8. Open billing and select the customer, patient, doctor, and prescription as needed.
9. For products marked `prescription_required` or `controlled_medicine`, billing requires a prescription and matching prescription line.
10. Finalizing the bill reduces batch stock, increments dispensed quantity on the linked prescription item, updates the prescription status, and calculates the next refill due date when tracking is enabled.
11. Cancelling a bill or processing a sales return reduces dispensed quantity, re-syncs prescription status, and re-calculates refill dates from remaining billed history.

Once any quantity has been dispensed from a prescription, the prescription becomes read-only so the dispensing trail remains stable.

## Billing Linkage

Sales invoices now store:

- `doctor_id`
- `prescription_id`
- `doctor_name`
- `prescription_number`

Sales invoice items now store:

- `prescription_item_id`

This lets bill detail, receipt printing, return handling, and future compliance reporting trace the specific prescription line used for dispensing.

## Demo Records

The local demo seeders now create:

- doctor `DOC-DEMO-001` / `Dr Demo`
- patient `PAT-DEMO-001` linked to that doctor
- prescription `RX-DEMO-001` with one demo line for `Sales Tablet`
- refill-tracked prescription `RX-REFILL-DEMO-001` with overdue bill `SI-RF-DEMO-001`

Seeder command:

```powershell
& .\.tools\php-8.3.33\php.exe artisan db:seed --class=DoctorPrescriptionDemoSeeder --force
```

## Boundary

This module does not yet implement:

- OCR or AI extraction from prescription attachments
- masked display or break-glass access workflows
- prescription communication outbox
- doctor visit scheduling or appointment management

Refill scheduling is documented separately in `docs/refills.md`. Controlled-medicine register foundation is documented separately in `docs/controlled-medicines.md`.
