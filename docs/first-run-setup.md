# First-run Setup

Phase 3 adds the installation wizard that prepares the app for store-specific operation before pharmacy workflows are implemented. Phase 4 connects the owner account from this wizard to local authentication and the owner role.

## Routes

- `/` redirects to `/setup` until setup is complete.
- `/setup` renders the Livewire setup wizard while setup is incomplete.
- `/status` remains available for diagnostics after setup completion and login.

## Stored Records

Setup completion creates records in one database transaction:

- `stores`
- `registered_pharmacists`
- `application_settings`
- `first_run_setup_steps`
- an owner `users` record with `is_owner` and `created_during_setup` set to true

The owner password is entered during setup and hashed by Laravel. The seeder does not create a default user. Phase 4 assigns this owner the `owner` role and signs the user in after setup completion.

## Settings Written

- `setup.completed`
- `setup.completed_at`
- `setup.store_id`
- `setup.owner_user_id`
- `billing.invoice_prefix`
- `billing.financial_year_starts_on`
- `printing.default_printer_name`
- `printing.receipt_printer_name`
- `backup.default_path`

## Boundary

The setup phase did not add inventory, POS, patients, prescriptions, sales, purchases, GST reports, or accounting workflows. Those remain future modules.
