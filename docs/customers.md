# Customers and Patients

Customer and patient records are now part of the offline local workflow.

## Routes

- `/customers`
- `/customers/create`
- `/customers/{customer}`
- `/customers/{customer}/edit`
- `/patients`
- `/patients/create`
- `/patients/{patient}`
- `/patients/{patient}/edit`

Customer routes require first-run setup and login. Viewing requires `customers.view`. Creating, editing, deleting/deactivating, and restoring requires `customers.manage`.

Patient routes require first-run setup and login. Viewing requires `patients.view`. Creating, editing, deleting/deactivating, and restoring requires `patients.manage`.

## Implemented Records

- `customers`
- `patients`

Customers store:

- name and customer code
- phone, email, and GSTIN
- address fields
- opening balance, credit limit, and outstanding balance
- loyalty points
- reminder, WhatsApp, and SMS consent
- notes
- active/deleted state

Patients store:

- optional linked customer
- full name and patient code
- phone and email
- date of birth and gender
- optional linked primary doctor plus primary doctor snapshot text
- address fields
- allergies and medical notes
- reminder, WhatsApp, and SMS consent
- notes
- active/deleted state

## Billing Linkage

Sales billing now supports:

- optional customer record selection
- optional patient record selection
- automatic customer fill when the selected patient belongs to a customer
- automatic doctor fill when the selected patient has a linked primary doctor
- preserved customer and patient snapshot fields on finalized bills
- linked bill history on customer and patient detail pages

Walk-in billing still works. Record linkage is optional.

## Doctor and Prescription Linkage

Patient detail pages now show:

- linked primary doctor
- prescription history
- billing history

Dedicated doctor and prescription workflows are documented in `docs/doctors-and-prescriptions.md`.

## Demo Records

The local demo seeder creates:

- customer `CUST-DEMO-001` / `Demo Family Account`
- patient `PAT-DEMO-001` / `Demo Patient`

Seeder command:

```powershell
& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
```

## Boundary

This module does not yet implement:

- refill schedules
- communication delivery outbox
- export and retention workflows
- masked-display or break-glass access workflows
