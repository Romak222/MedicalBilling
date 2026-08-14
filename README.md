# MedStore Foundation

Commercial Windows desktop pharmacy management system foundation for India-focused medical stores.

This repository is currently in Phase 10: controlled-medicine, refill, and cash-drawer foundation. It contains a Laravel 12 application with SQLite development storage, Blade views, Livewire, Livewire-bundled Alpine, Tailwind CSS, a protected health/status screen, NativePHP Desktop, a first-run setup wizard, local login, foundation access-control records, audit hooks, richer catalogue master records, supplier directory records, purchase order records, purchase receiving records, batch inventory records, sales billing records, customer, patient, doctor, prescription, controlled-medicine register, prescription-refill tracking, cash-drawer shift, and sales return records, plus documentation for the larger product.

## Current Stack

- Backend: Laravel 12.x
- PHP: 8.3+ because NativePHP Desktop requires it
- Desktop target: NativePHP Desktop 2.2
- Frontend: Blade, Livewire 4, Alpine via Livewire ESM bundle, Tailwind CSS 4
- Local database: SQLite
- LAN database target: MySQL or MariaDB in a later phase
- Tests: PHPUnit

Laravel 13 was not selected during Phase 1 because the installed global PHP runtime was 8.2.12 and Laravel 13 requires PHP 8.3+. Phase 2 added a project-local PHP 8.3.33 runtime under `.tools` for development commands without changing the global XAMPP install. NativePHP Desktop v2 requires PHP 8.3+, Laravel 11+, and Node 22+.

Official compatibility references:

- Laravel release/support policy: https://laravel.com/docs/13.x/releases
- Laravel 12 deployment requirements: https://laravel.com/docs/12.x/deployment
- NativePHP Desktop installation requirements: https://nativephp.com/docs/desktop/2/getting-started/installation
- NativePHP Desktop support policy: https://nativephp.com/docs/desktop/2/getting-started/support-policy

## Local Setup

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite }
php artisan migrate
npm run build
php artisan test
```

If the global `php` command is still PHP 8.2, run PHP/Composer commands through the project-local PHP runtime:

```powershell
& .\.tools\php-8.3.33\php.exe artisan test
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar install
```

For local browser testing:

```powershell
php artisan serve
npm run dev
```

Open `http://127.0.0.1:8000`.

## Status Screen

The `/status` route renders the system status screen after first-run setup and login. It reports:

- Application version
- Database connection and driver
- Offline core status
- Application data path
- Backup path
- Runtime information

The root route `/` redirects fresh installs to `/setup` until first-run setup is complete.

## First-run Setup

Phase 3 adds a Livewire setup wizard for:

- Store profile
- GSTIN/PAN/drug licence fields
- Registered pharmacist fields
- Invoice prefix and financial year start date
- Printer names and backup path
- Owner account creation

Setup completion is written in one database transaction. The owner password is supplied by the installer and hashed by Laravel. The database seeder intentionally creates no default user.

## Authentication and Access Control

Phase 4 adds local session login and logout. Setup completion assigns the owner role to the first owner account. The initial permission set covers system status, setup/settings management, user and role management, audit viewing, and sensitive-action approval.

Authentication events are recorded in `login_events` and `audit_events`. Status routes require the `system.status.view` permission after setup.

## Catalogue

Phase 5 adds the product and medicine catalogue foundation at `/products`.

Implemented catalogue records:

- Manufacturers
- Categories with optional parent categories
- Configurable tax rates
- Product types
- Dosage forms
- Schedule labels
- Units
- Products
- Product units and conversion factor
- Product barcodes
- HSN code field
- Prescription-required and controlled-medicine flags
- List-first product workspace with fixed sidebar
- Full-page read-only view, Add New Product, and edit workflows
- Product Options hub with separate view and CRUD detail pages for reusable manufacturers, categories, tax rates, units, product types, dosage forms, and schedule labels
- Delete/deactivate and restore actions

The catalogue does not store stock balances, batches, purchases, sales, or prices.

## Suppliers

Phase 6 starts the supplier and distributor foundation at `/suppliers`.

Implemented supplier records:

- Supplier profile and supplier code
- GSTIN and drug-licence fields
- Address, phone, email, and notes
- Payment terms
- Opening balance, credit limit, and outstanding balance using decimal columns
- Primary supplier contact
- Full-page read-only view, Add New Supplier, and edit workflows
- Delete/deactivate and restore actions
- Supplier permissions and audit events

## Purchase Orders

Phase 6 adds the purchase-order foundation at `/purchases/orders`.

Implemented purchase records:

- Purchase order header with supplier snapshot, order number, reference, ordered date, expected date, payment terms, notes, and status
- Purchase order line items with product snapshot, unit, quantity, free quantity, unit cost, line discount, tax rate, line subtotal, line tax, and line total
- Draft, sent, cancelled, and reopened status flow
- Full-page read-only view, Add Purchase Order, and edit workflows
- Status filters and summary counters
- Purchase permissions and audit events

The purchase-order foundation does not create purchase invoices, supplier payments, supplier ledgers, batches, stock movements, accounting postings, or receiving behavior yet.

## Purchase Receiving and Inventory

Phase 7 adds purchase receiving at `/purchases/invoices` and batch inventory at `/inventory/batches`.

Implemented receiving and inventory records:

- Purchase invoices and purchase invoice items
- Product batches with batch number, MFG date, expiry date, MRP, purchase rate, sale rate, and available quantity
- Immutable stock movements for finalized receiving
- Draft purchase invoices that can be edited or cancelled before stock is received
- Finalize action that creates/updates batches and writes stock movement records
- Inventory batch list with available, expiring, expired, and all filters

Stock is not stored directly on products. Product availability is projected through batch records that are updated from stock movements.

## Billing

Phase 8 adds POS billing at `/billing/sales`.

Implemented billing records:

- Sales invoices
- Sales invoice items
- Optional linked customer, patient, doctor, and prescription records on each bill
- Prescription-line linkage for regulated products
- Batch selection for each sale line
- Cash/card/UPI/mixed payment method field
- Paid amount and change amount
- Finalized bill stock consumption
- Bill cancellation with stock reversal
- Barcode or batch quick scan
- Hold and resume bills
- Printable receipt view
- Sales returns with partial quantity support
- Optional manual restock per returned line
- Return history from bill detail pages

Billing keeps walk-in entry available while also supporting linked customer, patient, doctor, and prescription records with preserved invoice snapshots.

## Cash Drawer

Phase 10 now includes `/cash-drawer` for one active local drawer shift at a time.

Implemented cash-control records:

- Opening float and operator handover notes
- Cash-in and cash-out movement rows with reasons and audit events
- Automatic attachment of active shifts to cash sales and cash refunds
- Expected closing cash, counted cash, and signed variance
- Shift detail pages with linked cash bills, refunds, and manual movements
- Dedicated `cash_drawer.view` and `cash_drawer.manage` permissions
- Local demo shift `CD-DEMO-001` with a visible variance and manual movements

Cash drawer controls do not replace accounting journals, customer/supplier ledgers, bank reconciliation, GST reports, or hardware-specific cash-drawer triggers.

## Customers and Patients

Phase 8 now includes `/customers` and `/patients`.

Implemented record coverage:

- Full-page customer list, add, view, edit, delete/deactivate, and restore workflows
- Full-page patient list, add, view, edit, delete/deactivate, and restore workflows
- Customer contact, GSTIN, balances, loyalty, and consent fields
- Patient contact, DOB, gender, allergies, medical notes, linked primary doctor, doctor snapshot text, and consent fields
- Optional billing linkage from finalized sales invoices
- Local demo records for customer and patient workflow checks

## Doctors and Prescriptions

Phase 9 now includes `/doctors` and `/prescriptions`.

Implemented record coverage:

- Full-page doctor list, add, view, edit, delete/deactivate, and restore workflows
- Full-page prescription list, add, view, edit, archive, and restore workflows
- Doctor registration, specialization, clinic, contact, address, and notes fields
- Prescription header, attachment, and pharmacist-note fields
- Prescription medicine lines with dosage instructions and prescribed versus dispensed quantity tracking
- Prescription-line refill interval, reminder lead, last-dispensed, and next-due tracking
- Prescription history on patient detail pages
- Optional billing linkage from finalized sales invoices
- Prescription-required and controlled-medicine validation inside billing
- Local demo doctor and prescription workflow records

## Refill Tracker

Phase 10 now includes `/prescriptions/refills`.

Implemented record coverage:

- Refill tracker list and detail pages for prescription lines with repeat-dispense timing
- Refill interval and reminder-lead controls on prescription lines
- Auto-updated last-dispensed and next-refill-due dates from finalized billing history
- Refill recalculation after sales returns and bill cancellation
- Local overdue refill demo bill `SI-RF-DEMO-001`

## Controlled Medicines

Phase 10 now includes `/controlled-medicines`.

Implemented record coverage:

- Read-only controlled-medicine register list and detail pages
- Automatic register entries for finalized controlled-medicine sales
- Automatic reversal entries for bill cancellation and sales returns
- Linkage back to patient, doctor, prescription, bill, return, and batch snapshots
- Dedicated `controlled_medicines.view` permission
- Local demo controlled-medicine bill `SI-CM-DEMO-001`

Broader statutory exports, cashier shifts, hardware-specific receipt printer configuration, GST reports, and accounting postings remain pending.

## NativePHP Status

NativePHP Desktop is installed and configured. The main window opens the root route with a 1366 x 768 default size and 1024 x 700 minimum size.

```powershell
& .\.tools\php-8.3.33\php.exe artisan native:run
```

The desktop window defaults are tracked in `config/pharmacy.php`:

- Width: 1366
- Height: 768
- Title: `NATIVEPHP_WINDOW_TITLE`

Production build/publish checks are still preliminary and should be hardened before release signing or installer distribution.

Phase 2 produced an unpacked Windows app at `nativephp/electron/dist/win-unpacked/medstore-foundation.exe`. A final NSIS installer `.exe` is still pending a clean build/reset pass and secure bundling/signing work.

## Documentation

- `docs/architecture.md`
- `docs/modules.md`
- `docs/database-plan.md`
- `docs/offline-strategy.md`
- `docs/security-and-audit.md`
- `docs/printing-and-hardware.md`
- `docs/development-roadmap.md`
- `docs/nativephp-desktop.md`
- `docs/first-run-setup.md`
- `docs/access-control.md`
- `docs/catalogue.md`
- `docs/suppliers.md`
- `docs/purchases.md`
- `docs/inventory.md`
- `docs/billing.md`
- `docs/customers.md`
- `docs/doctors-and-prescriptions.md`
- `docs/refills.md`
- `docs/controlled-medicines.md`
- `docs/cash-drawer.md`
- `PROGRESS.md`

## Phase Boundary

Do not start supplier payments, supplier ledger, or accounting implementation until the current Phase 10 controlled-medicine, refill, and cash-drawer foundation is accepted.
