# Progress

## Completed Work

- Inspected repository; it was empty except for `.git`.
- Checked local runtime:
  - PHP 8.2.12
  - Composer 2.10.1
  - Node 25.2.1
  - npm 11.6.2
  - Laravel Framework 12.65.0
  - Livewire 4.4.0
  - Project-local PHP 8.3.33 added under `.tools`
  - NativePHP Desktop 2.2.1 installed
  - nativephp/php-bin 1.2.0 installed
  - PDO SQLite enabled
  - PDO MySQL enabled
  - MySQL/MariaDB CLI not found
  - sqlite3 CLI not found
- Verified official compatibility:
  - Laravel 13 requires PHP 8.3+
  - Laravel 12 supports PHP 8.2+
  - NativePHP Desktop v2 requires PHP 8.3+, Laravel 11+, Node 22+
- Initialized Laravel 12.
- Configured SQLite for local development.
- Installed Livewire 4.
- Configured Livewire-bundled Alpine through Vite.
- Kept Tailwind CSS local through Vite.
- Added pharmacy configuration in `config/pharmacy.php`.
- Added system status service and Livewire status panel.
- Added the `/status` status screen and later routed `/` through the setup-completion gate.
- Created foundation documentation.
- Added tests for app boot and SQLite connectivity.
- Ran formatting with Pint.
- Ran PHPUnit tests successfully.
- Ran Vite production build successfully.
- Attempted a NativePHP Composer dry run under global PHP 8.2.12; it failed because `nativephp/desktop` 2.2.1 requires PHP `^8.3`.
- Installed project-local PHP 8.3.33 from the official Windows PHP release zip.
- Installed NativePHP Desktop 2.2.1 using PHP 8.3.33.
- Ran `native:install`.
- Configured the main desktop window at 1366 x 768, then updated it to open the app root for setup gating.
- Ran `native:run`; Electron and bundled PHP processes started successfully, then were stopped after verification.
- Ran a Windows x64 NativePHP build check.
- Produced an unpacked Windows app at `nativephp/electron/dist/win-unpacked/medstore-foundation.exe`.
- Began Phase 3 after Phase 2 handoff.
- Added first-run setup migrations for stores, registered pharmacists, application settings, setup steps, and setup user flags.
- Added the Livewire first-run setup wizard at `/setup`.
- Redirected fresh installs from `/` to `/setup` until setup is complete.
- Changed the NativePHP main window to open the app root so fresh installs reach the setup wizard.
- Added transactional setup completion for store profile, legal fields, pharmacist, billing defaults, printer and backup paths, and owner account creation.
- Removed the seeded default user.
- Added Phase 3 test coverage.
- Began Phase 4 after Phase 3 handoff.
- Added local login and logout routes.
- Added roles, permissions, role-user, and permission-role tables.
- Added owner role and foundation permissions.
- Assigned the owner role during first-run setup and through a Phase 4 data migration for existing owner users.
- Protected dashboard/status routes after setup completion.
- Added `login_events` and `audit_events`.
- Added audit logging for setup completion, successful login, failed login, and logout.
- Added Phase 4 authentication and permission tests.
- Began Phase 5 after Phase 4 handoff.
- Added catalogue tables for manufacturers, categories, tax rates, products, product units, and product barcodes.
- Added product and medicine catalogue models.
- Added transactional catalogue product creation with audit logging.
- Added catalogue permissions and assigned them to the owner role.
- Added the protected `/catalogue` route and Livewire catalogue manager.
- Added product master fields for generic name, composition, product type, HSN code, schedule label, prescription-required flag, and controlled-medicine flag.
- Added unit conversion and barcode records without adding stock balances, batches, purchases, or billing.
- Added Phase 5 catalogue tests.
- Reworked the catalogue into a professional fixed-sidebar product workspace.
- Added `/products` as the primary product-list route while keeping `/catalogue` available.
- Added top-right Add New Product flow.
- Added edit, safe delete/deactivate, and restore actions.
- Added audit events for product update, deactivation, and restoration.
- Added product summary counters, active/all/deleted filters, and richer search.
- Replaced the product side drawer with full-page add/edit product screens.
- Added `/catalogue/masters` for product options: manufacturers, categories, tax rates, units, product types, dosage forms, and schedule labels.
- Added `unit_masters`, `product_type_masters`, `dosage_form_masters`, and `schedule_label_masters` for reusable options.
- Added parent-category selection for category masters.
- Added product-form barcode capture controls for manual entry, USB keyboard scanners, native camera scanning, and a bundled ZXing camera-scanner fallback.
- Reworked Product Options into a hub plus separate CRUD detail pages for each option type.
- Added read-only View pages and row actions for products and product option records.
- Documented additional product details to consider later without mixing in stock, purchase, or POS.
- Began Phase 6 after Phase 5 handoff.
- Added supplier and distributor tables for supplier profiles and primary contacts.
- Added supplier permissions and assigned them to the owner role.
- Added supplier models and transactional supplier directory service.
- Added protected `/suppliers` list, `/suppliers/create`, `/suppliers/{supplier}`, and `/suppliers/{supplier}/edit` routes.
- Added full-page supplier list, add, view, edit, delete/deactivate, and restore workflows.
- Added supplier fields for supplier code, GSTIN, drug licence, address, phone, email, payment terms, opening balance, credit limit, outstanding balance, notes, and primary contact.
- Added supplier audit events for create, update, deactivate, and restore.
- Kept purchase orders, purchase invoices, supplier payments, supplier ledgers, batches, stock movements, accounting, and POS out of this supplier-directory phase.
- Added Phase 6 supplier tests.
- Added purchase order and purchase order item tables.
- Added purchase permissions and assigned them to the owner role.
- Added purchase order models and transactional purchase-order manager.
- Added protected `/purchases/orders`, `/purchases/orders/create`, `/purchases/orders/{purchaseOrder}`, and `/purchases/orders/{purchaseOrder}/edit` routes.
- Added full-page purchase order list, add, view, edit, send, cancel, and reopen workflows.
- Added purchase order fields for supplier, order number, reference number, ordered date, expected date, payment terms, notes, status, subtotal, discount, tax, and total.
- Added purchase order item fields for product snapshot, unit, quantity, free quantity, unit cost, discount, tax rate, line subtotal, line tax, and line total.
- Added purchase order audit events for create, update, sent, cancelled, and reopened.
- Kept purchase invoices, receiving, supplier payments, supplier ledgers, batches, stock movements, accounting, and POS out of this purchase-order foundation.
- Added local demo supplier and purchase-order entries for draft, sent, and cancelled statuses.
- Added Phase 6 purchase-order tests.
- Began Phase 7 after confirming expiry belongs to batch inventory, not product master records.
- Added product batch, purchase invoice, purchase invoice item, and stock movement tables.
- Added inventory permissions and assigned them to the owner role.
- Added product batch, purchase invoice, purchase invoice item, and stock movement models.
- Added transactional purchase receiving service.
- Added protected `/purchases/invoices`, `/purchases/invoices/create`, `/purchases/invoices/{purchaseInvoice}`, and `/purchases/invoices/{purchaseInvoice}/edit` routes.
- Added protected `/inventory/batches` route.
- Added full-page purchase receiving list, add, view, edit, cancel-draft, and finalize workflows.
- Added receiving fields for supplier invoice number, supplier, optional purchase order, invoice date, received date, notes, product, batch number, MFG date, expiry date, quantity, free quantity, MRP, purchase rate, sale rate, discount, and tax.
- Added inventory batch list with available, expiring, expired, and all filters.
- Finalizing a purchase invoice creates or updates product batches and writes immutable stock movement rows.
- Draft purchase invoices do not affect stock.
- Added purchase invoice audit events for create, update, cancel, and finalize.
- Added local demo receiving records: one draft invoice, one finalized invoice, one batch, and one stock movement.
- Added Phase 7 purchase receiving and inventory tests.
- Began Phase 8 after Phase 7 receiving handoff.
- Added sales invoice and sales invoice item tables.
- Added sales permissions and assigned them to the owner role.
- Added sales invoice and sales invoice item models.
- Added transactional sales billing service.
- Added protected `/billing`, `/billing/sales`, `/billing/sales/create`, and `/billing/sales/{salesInvoice}` routes.
- Added full-page billing list, new bill, view bill, and cancel bill workflows.
- Added billing fields for invoice number, bill date, walk-in customer name/phone, payment method, paid amount, change amount, notes, batch, quantity, unit price, discount, tax, and totals.
- Finalizing a sales invoice consumes non-expired, unblocked batch stock and writes immutable negative stock movement rows.
- Cancelling a finalized sales invoice restores batch stock and writes immutable reversal stock movement rows.
- Added sales invoice audit events for create and cancel.
- Added local demo sales bill and sale stock movement.
- Added Phase 8 sales billing tests.
- Added POS barcode/batch quick scan for billing, resolving product barcodes to the earliest expiring available batch.
- Added held sales bills so counter staff can hold, resume, or discard paused bills without stock impact.
- Added a printable sales receipt page from finalized bill details.
- Added local demo barcode `9900000000017` and held bill `HOLD-DEMO-001` for counter workflow testing.
- Added Phase 8 tests for barcode quick scan, held bill resume, and receipt viewing.
- Added sales return and sales return item tables for partial or full bill returns.
- Added bill-linked sales return create and detail pages with refund method, refund amount, and pharmacist note fields.
- Added optional manual restock per returned line, with stock movements only for approved restock lines.
- Added a rule that finalized sales invoices with returns cannot be cancelled.
- Added Phase 8 tests for sales return creation, partial return limits, optional restock, and return-linked cancellation blocking.
- Added customer and patient tables with dedicated permissions and owner-role assignment.
- Added protected `/customers`, `/customers/create`, `/customers/{customer}`, `/customers/{customer}/edit`, `/patients`, `/patients/create`, `/patients/{patient}`, and `/patients/{patient}/edit` routes.
- Added full-page customer and patient list, add, view, edit, delete/deactivate, and restore workflows.
- Added customer fields for code, GSTIN, balances, loyalty points, reminder consent, WhatsApp consent, SMS consent, and notes.
- Added patient fields for linked customer, patient code, DOB, gender, doctor-name text, allergies, medical notes, consent flags, and notes.
- Added customer and patient audit events for create, update, deactivate, and restore actions.
- Linked billing to optional customer and patient records while preserving invoice snapshot fields for customer and patient names/phones.
- Added local demo customer `CUST-DEMO-001 / Demo Family Account` and patient `PAT-DEMO-001 / Demo Patient`.
- Added Phase 8 tests for customer and patient workflows plus bill linkage to customer and patient records.
- Began Phase 9 after the customer and patient handoff.
- Added doctor, prescription, and prescription item tables.
- Added doctor and prescription permissions and assigned them to the owner role.
- Added protected doctor and prescription routes plus local prescription attachment download.
- Added full-page doctor list, add, view, edit, delete/deactivate, and restore workflows.
- Added full-page prescription list, add, view, edit, archive, restore, and detail workflows.
- Added doctor fields for registration number, specialization, clinic, phone, email, address, and notes.
- Added prescription fields for patient and doctor linkage, snapshot names, prescription date, validity date, attachment metadata, pharmacist notes, status, and dispensed-quantity tracking.
- Added patient primary-doctor linkage while preserving doctor-name snapshot text on the patient record.
- Linked billing to optional doctor and prescription records while preserving invoice snapshot fields for doctor name and prescription number.
- Required prescription and prescription-line linkage for prescription-required and controlled-medicine products during billing.
- Updated bill cancellation and sales returns to reverse linked dispensed quantities and re-sync prescription status.
- Added prescription history to patient detail pages and doctor/prescription detail linkage across billing records.
- Added local demo doctor `DOC-DEMO-001 / Dr Demo` and prescription `RX-DEMO-001`.
- Added Phase 9 tests for doctor and prescription workflows plus prescription-linked billing.
- Began Phase 10 after the doctor and prescription handoff.
- Added controlled-medicine register entry table and access migration.
- Added protected `/controlled-medicines` list and `/controlled-medicines/{entry}` detail routes.
- Added read-only controlled-medicine register list and detail pages in the fixed sidebar workspace.
- Added automatic controlled-medicine register entries for finalized bills on controlled products.
- Added automatic reversal entries for cancelled bills and finalized sales returns on controlled products.
- Added controlled-medicine register linkage to product, batch, customer, patient, doctor, prescription, bill, and return context.
- Added local demo controlled product `CM-DEMO-001`, batch `CMB-DEMO-001`, and bill `SI-CM-DEMO-001`.
- Added Phase 10 tests for controlled-medicine register access plus sale, cancellation, and return register behavior.
- Continued Phase 10 with prescription refill scheduling and repeat-dispense reminder tracking.
- Added refill interval, reminder lead, last-dispensed, and next-due fields on prescription items.
- Added automatic refill-date recalculation from finalized billing history, sales returns, and bill cancellation.
- Added protected `/prescriptions/refills` list and `/prescriptions/refills/{prescriptionItem}` detail routes.
- Added refill controls to prescription-line create/edit pages and refill summary data to prescription detail pages.
- Added local demo refill prescription `RX-REFILL-DEMO-001` and overdue bill `SI-RF-DEMO-001`.
- Added Phase 10 tests for refill tracker pages plus sale, return, and cancellation recalculation behavior.
- Continued Phase 10 with cash drawer shift controls and close reconciliation.
- Added `cash_drawer_shifts` and `cash_drawer_entries` with decimal money fields, one-open-shift protection, manual cash-in/cash-out reasons, and signed closing variance.
- Added `cash_drawer_shift_id` linkage to sales invoices and sales returns; active cash sales and cash refunds attach automatically to the open shift.
- Added protected `/cash-drawer` workspace and `/cash-drawer/{cashDrawerShift}` detail route with fixed-sidebar navigation.
- Added `cash_drawer.view` and `cash_drawer.manage` permissions and owner-role assignment migration.
- Added cash drawer detail views for linked cash bills, cash refunds, manual movements, expected cash, counted cash, and variance notes.
- Added Phase 10 tests for cash drawer lifecycle, decimal reconciliation, billing linkage, duplicate open-shift prevention, and access control.
- Continued Phase 10 with audited settings and receipt-printer configuration.
- Added protected `/settings` with store profile, registered pharmacist, billing defaults, printer names, paper width, receipt copies, footer, and backup path controls.
- Added transactional settings updates with `settings.updated` audit events and no sensitive-value logging.
- Connected configured store identity, receipt paper width, and receipt footer to the printable sales receipt.
- Added Phase 10 settings and receipt configuration tests.
- Continued Phase 10 with operational reporting and controlled-medicine CSV export.
- Added `reports.view` permission and owner-role assignment migration.
- Added protected `/reports` workspace with date-bounded sales, returns, inventory expiry, controlled-medicine, refill, and cash-drawer summaries.
- Added date-bounded controlled-medicine register CSV download with preserved event snapshots.
- Added Phase 10 reporting and export access-control tests.
- Continued production hardening with local user and role administration.
- Added explicit active-user state and blocked inactive accounts during login without revealing account state.
- Added built-in manager, pharmacist, and cashier roles with least-privilege permission sets.
- Added protected `/access` workspace for owner-controlled staff creation, editing, role assignment, disabling, restoration, and role visibility.
- Protected the owner account from disabling or owner-role removal and audited all access changes.
- Added access-management and inactive-login tests.
- Continued production hardening with the accounting foundation.
- Added configurable system chart-of-accounts records for cash, payment receivables, inventory, tax, payable, revenue, COGS, and customer credit.
- Added immutable posted journal entries and journal lines with balanced double-entry validation, source-document linkage, and reversal references.
- Added automatic accounting postings for finalized sales, sale cancellations, sales returns, and purchase receipts.
- Added protected `/accounting` journal review and journal detail pages with date filters, account activity, debit/credit control totals, and balance checks.
- Added accounting permissions, owner/manager access assignment, idempotent source posting, and accounting feature tests.

## Current Phase

Phase 10: controlled-medicine, refill, cash-drawer, settings, operational reporting, access, and accounting foundation.

## Pending Work

- Produce a final NSIS installer `.exe` from a clean build/reset workflow.
- Add a proper app icon and product metadata.
- Add application signing and secure bundle strategy before commercial distribution.
- Continue Phase 10 with statutory GST exports, customer/supplier sub-ledgers, payment reconciliation, stock adjustments, or deeper regulated reporting.

## Known Issues

- Global PHP is still XAMPP PHP 8.2.12; NativePHP commands must use `.tools/php-8.3.33/php.exe` unless global PHP is upgraded.
- MySQL/MariaDB CLI is not available locally.
- sqlite3 CLI is not available locally; Laravel PDO SQLite is used through PHP.
- An empty local `__laravel_scaffold` directory may remain because cleanup was blocked by the command policy. It is empty and not tracked by Git.
- The first NativePHP install required manually pre-caching the large `nativephp/php-bin` archive after Composer download timeouts/transient failures.
- NativePHP build warns that the app bundle is insecure because secure bundling is not configured yet.
- NativePHP Electron dependency install reports npm audit issues inside NativePHP/Electron build dependencies; these are upstream build-tool dependencies and should be reviewed before production release.
- The first Windows build attempt failed while downloading Electron from GitHub. A retry created an unpacked app and NSIS staging archive, but no final installer `.exe` was present before the duplicate build was stopped.

## Verification Results

```powershell
composer format
# Passed; Pint applied style fixes.

php artisan test
# Passed: 3 tests, 7 assertions.

npm run build
# Passed; Vite built production assets.

composer require nativephp/desktop --dry-run --no-interaction
# Failed as expected: nativephp/desktop 2.2.1 requires PHP ^8.3, current PHP is 8.2.12.
```

Phase 2 verification completed with packaging caveats.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint
# Passed; Pint applied style fixes.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 7 tests, 24 assertions.

npm run build
# Passed; Vite built production assets.

& .\.tools\php-8.3.33\php.exe artisan native:run --no-interaction --no-dependencies --no-queue --no-focus
# Started NativePHP/Electron successfully; stopped after verification.

& .\.tools\php-8.3.33\php.exe artisan native:build win x64 --no-interaction
# Reached Electron packaging and produced an unpacked Windows app.
# Final installer .exe was not produced during this pass.
```

Phase 3 verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 3 migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 7 tests, 24 assertions.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 10 settings and receipt configuration verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 74 tests, 619 assertions.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 10 operational reporting verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; reporting access records applied.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 78 tests, 633 assertions.

npm run build
# Passed; Vite built production assets.
```

Phase 10 access-management verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; active-user and staff-role records applied.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 82 tests, 653 assertions.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 10 accounting verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; accounting tables, chart-of-accounts records, and access records applied.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 85 tests, 679 assertions.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 4 verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 4 migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 12 tests, 53 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.

Invoke-WebRequest -Uri http://127.0.0.1:8000/setup -UseBasicParsing -TimeoutSec 10
# Passed: 200 OK.
```

Phase 5 verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 5 catalogue and product option migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 23 tests, 162 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 6 supplier-directory verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 6 supplier migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 29 tests, 215 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 6 purchase-order verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 6 purchase-order migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 35 tests, 271 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 7 purchase receiving and batch inventory verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed; Pint applied route/test style fixes.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 7 receiving and inventory migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 40 tests, 330 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 8 POS billing verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; Phase 8 sales billing migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 46 tests, 369 assertions.

& .\.tools\php-8.3.33\php.exe vendor\bin\pint --test
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

npm run build
# Passed; Vite built production assets.
```

Phase 8 counter workflow verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; held sales bill migration applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 47 tests, 383 assertions.
```

Phase 8 sales return verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; sales return migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed; Pint cleaned the sales return form.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 50 tests, 414 assertions.
```

Phase 8 customer and patient verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; customer, patient, and sales invoice linkage migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar dump-autoload --no-scripts
# Passed.

& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
# Passed; local demo customer and patient entries created.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 56 tests, 474 assertions.

npm run build
# Passed; Vite built production assets.
```

Phase 9 doctor and prescription verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; doctor, prescription, and billing-link migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 61 tests, 521 assertions.
```

Phase 10 controlled-medicine register verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --strict
# Passed.

& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; controlled-medicine register migrations applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
# Passed; local demo customer, patient, doctor, prescription, and controlled-medicine entries created.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 64 tests, 543 assertions.

npm run build
# Passed; Vite built production assets.
```

Phase 10 refill-tracker verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; refill-tracking migration applied to the local SQLite database.

& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
# Passed; local demo refill, prescription, and controlled-medicine records created or refreshed.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 67 tests, 576 assertions.

npm run build
# Passed; Vite built production assets.
```

Phase 10 cash-drawer verification completed for the Laravel app.

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
# Passed; cash-drawer tables, sales links, and access records applied.

& .\.tools\php-8.3.33\php.exe artisan db:seed --class=CustomerPatientDemoSeeder --force
# Passed; local demo shift CD-DEMO-001 created or refreshed with a -5.00 variance.

& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar format
# Passed.

& .\.tools\php-8.3.33\php.exe artisan test
# Passed: 71 tests, 603 assertions.

npm run build
# Passed; Vite built production assets.
```

## Important Decisions

- Laravel 12 was selected instead of Laravel 13 due to the installed PHP 8.2.12 runtime.
- NativePHP commands use the project-local PHP 8.3.33 runtime to avoid changing the global XAMPP PHP install.
- Development SQLite remains at `database/database.sqlite`.
- Packaged production data must later move to the Windows application-data directory.
- Online integrations are outside the offline core and must use an outbox pattern.
- First-run setup is stored in local database tables, not hardcoded into `.env`.
- The owner account is created only from installer-entered credentials.
- Status/dashboard access requires authentication and `system.status.view` after setup completion.
- Setup and authentication events are stored in local audit tables.
- Catalogue access requires `catalogue.view`; product creation requires `catalogue.manage`.
- Catalogue stores master data only; stock remains a future immutable movement ledger.
- Supplier access requires `suppliers.view`; supplier creation, editing, delete/deactivation, and restoration require `suppliers.manage`.
- Suppliers store profile/contact/terms data only; purchase documents, supplier ledger entries, stock movements, and accounting postings remain future phases.
- Purchase access requires `purchases.view`; purchase order creation, editing, sending, cancellation, and reopening require `purchases.manage`.
- Purchase orders are request/planning documents only; purchase invoices, receiving, stock movements, supplier ledgers, payments, accounting postings, and POS remain future phases.
- Inventory access requires `inventory.view`; receiving finalization requires `inventory.manage`.
- Product expiry, MFG date, MRP, purchase rate, sale rate, and stock quantity are batch-level fields, not product master fields.
- Stock intake is recorded through immutable `stock_movements` and reflected in batch available quantities.
- Billing access requires `sales.view`; bill creation and cancellation require `sales.manage`.
- Finalized bills consume batch stock using immutable stock movements. Bill cancellation reverses stock through new movement rows instead of deleting movement history.
- Held bills do not reserve or reduce stock; stock is affected only when a bill is finalized.
- POS quick scan accepts product barcodes or batch numbers. Product barcodes resolve to the earliest expiring available batch.
- Sales receipt printing is an HTML print view; printer hardware setup remains a later configuration phase.
- Customer medicine returns are recorded as separate return documents, not bill cancellation.
- Returned packs are not restocked automatically; each line must be manually approved for restock and must still be saleable and within expiry.
- Once a bill has recorded returns, the bill cannot be cancelled because reversal must stay traceable through the return document trail.
- Customer and patient records were pulled into Phase 8 so billing can link to reusable profiles while still preserving per-invoice name and phone snapshots.
- Patient allergy and medical-note fields are stored behind dedicated patient permissions, with finer-grained access controls to be designed later.
- Controlled-medicine register entries are derived from finalized billing, bill cancellation, and sales returns rather than manual operator entry.
- Refill due dates are derived from surviving finalized billing lines on each prescription item, so partial returns and bill cancellation re-calculate reminder dates from net dispensed history.
- Refill tracking reuses `prescriptions.view` and prescription-line data rather than introducing a separate editable refill document type in this phase.
- Cash drawer reconciliation uses integer cents for arithmetic while persisting decimal money columns.
- Cash sales and cash refunds attach to the active drawer shift; non-cash payments remain outside drawer totals.

## Commands Needed

```powershell
composer install
npm install
php artisan migrate
npm run build
php artisan test
composer format
```

NativePHP with the project-local PHP 8.3 runtime:

```powershell
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar install
& .\.tools\php-8.3.33\php.exe artisan native:install
& .\.tools\php-8.3.33\php.exe artisan native:run
```

For NativePHP builds on this machine, prepend project PHP 8.3 to `PATH` so NativePHP's internal `composer install --no-dev` uses PHP 8.3 instead of global XAMPP PHP 8.2:

```powershell
$env:Path = "$(Get-Location)\.tools\php-8.3.33;$env:Path"
& .\.tools\php-8.3.33\php.exe artisan native:build win x64 --no-interaction
```
