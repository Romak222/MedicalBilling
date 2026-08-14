# MedStore Pharmacy Management System

## User Manual

**Document version:** 1.0
**Application phase:** Phase 10 production foundation
**Audience:** Store owners, pharmacists, managers, cashiers, and system administrators
**Operating model:** Local-first single-computer pharmacy workspace

This manual explains how to operate the current MedStore pharmacy management system safely. It is written from the actual application workflow and route structure in this repository.

MedStore is designed for local pharmacy operations including catalogue management, purchasing, batch inventory, billing, returns, patients, prescriptions, controlled-medicine register review, refill follow-up, cash drawer reconciliation, accounting review, reports, local backups, and workstation readiness.

This manual does not replace pharmacy law, pharmacist supervision, tax advice, drug-licence requirements, or local operating procedures. The store owner remains responsible for configuring the system according to the applicable jurisdiction and for reviewing all regulated records.

## 1. Important Operating Rules

Follow these rules on every installation:

1. Never share a user account. Every operator must sign in with their own account.
2. Never use a public or shared default password. The first owner password is created during setup.
3. Finalized sales, purchases, returns, stock movements, journals, and controlled-medicine register entries are historical records. Correct them through the supported reversal or return workflow instead of deleting history.
4. Record stock adjustments only after a physical count or an approved investigation.
5. Confirm expiry, batch, quantity, price, tax, prescription, and patient details before finalizing a transaction.
6. Keep the database and backup directory on a protected local disk. Do not expose the database file over a shared network drive.
7. Create and verify backups before upgrades, major data corrections, or restore operations.
8. Protect patient, prescription, allergy, medical-note, and controlled-medicine data from unauthorized viewing.
9. Use the local project PHP 8.3 runtime for development commands when the computer's global PHP is 8.2.
10. Multi-counter LAN support is intentionally disabled in this release. The current operating mode is one local workstation and one active local cash drawer shift.

## 2. What the System Covers

### Main modules

| Module | Purpose | Main screen |
| --- | --- | --- |
| Dashboard | Daily sales, stock risk, refill, payable, cash, and system-control overview | `/` |
| System Status | Database, runtime, local paths, offline readiness, backup, and hardware diagnostics | `/status` |
| Products | Medicine and product catalogue records | `/products` |
| Product Options | Manufacturers, categories, tax rates, units, types, dosage forms, and schedule labels | `/catalogue/masters` |
| Suppliers | Supplier and distributor profiles, balances, ledgers, and payments | `/suppliers` |
| Purchase Orders | Planned purchase requests and supplier order tracking | `/purchases/orders` |
| Purchase Receiving | Supplier invoices and stock intake | `/purchases/invoices` |
| Inventory | Product batches, expiry, availability, movements, and adjustments | `/inventory/batches` |
| Billing | POS sales, barcode entry, hold/resume, receipts, cancellation, and returns | `/billing/sales` |
| Customers | Customer accounts and billing history | `/customers` |
| Patients | Patient identities, allergies, notes, and linked clinical history | `/patients` |
| Doctors | Doctor directory and registration information | `/doctors` |
| Prescriptions | Prescription capture, attachments, dispensing, and refill timing | `/prescriptions` |
| Controlled Medicines | Read-only controlled-medicine dispensing register | `/controlled-medicines` |
| Refills | Prescription repeat-dispense follow-up queue | `/prescriptions/refills` |
| Cash Drawer | Opening, cash movements, closing, and variance | `/cash-drawer` |
| Accounting | Journals, account activity, ledgers, and settlement reconciliation | `/accounting` |
| Reports | Operational reports and controlled-medicine export | `/reports` |
| GST Reports | Date-bounded GST working report and CSV export | `/reports/gst` |
| Hardware | Windows printer discovery and printer test page | `/hardware` |
| Backups | Database backup creation, validation, and restore | `/backups` |
| Access | Local users and built-in role assignment | `/access` |
| Settings | Store, billing, receipt, printer, and backup settings | `/settings` |

### Core data model in plain language

- A **product** is the catalogue definition, such as a medicine name, strength, unit, barcode, tax rate, and flags.
- A **batch** is the physical stock received for a product. Batch number, expiry, MRP, purchase rate, sale rate, and available quantity belong to the batch.
- A **stock movement** records how quantity entered, left, returned to stock, or was adjusted.
- A **sale** records what was dispensed to a customer or patient and which batch was used.
- A **prescription** records the clinical instruction and the quantity dispensed against each line.
- A **journal** records the accounting effect of a finalized financial transaction.
- An **audit event** records important user and system actions.

## 3. Starting the Application

### Browser development mode

From the project directory, use the project-local PHP runtime:

```powershell
& .\.tools\php-8.3.33\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Open:

```text
http://127.0.0.1:8000
```

If port 8000 is already in use, start the server on another port and use that port in the browser.

### Native desktop mode

NativePHP Desktop is configured for the Windows desktop target. Development can be started with:

```powershell
& .\.tools\php-8.3.33\php.exe artisan native:run
```

Use the packaged desktop build only after the application has been tested on the target workstation. A production installer and signing process are still release work.

### PHP version requirement

NativePHP and the current application dependencies require PHP 8.3 or newer. If `php artisan serve` reports that the computer is running PHP 8.2, use:

```powershell
& .\.tools\php-8.3.33\php.exe artisan serve
```

Do not bypass Composer platform checks in a real installation.

## 4. First-run Setup

Open `/setup` on a fresh installation. The wizard has five steps. Setup completion is transactional: the store profile, pharmacist, settings, and owner account are saved together or not saved.

### Step 1: Store Profile

Enter:

- Store code. Use a short unique code such as `MAIN-STORE`.
- Store name.
- Legal name, if different from the store name.
- Address lines, city, state, and postal code.
- Store phone and email.

The store code may contain letters, numbers, hyphens, and underscores. Use a stable code because it becomes part of the store identity.

### Step 2: Licences and Pharmacist

Enter the store's available legal and pharmacy information:

- GSTIN.
- PAN.
- Drug licence number and validity date.
- Registered pharmacist name.
- Pharmacist registration number.
- Council name.
- Pharmacist licence validity date.
- Pharmacist phone and email.

The registered pharmacist name is required. Other legal fields may be completed according to the store's records.

### Step 3: Operations

Enter:

- Invoice prefix, such as `INV` or `MED`.
- Financial year start date.
- Default printer name, if known.
- Receipt printer name, if known.
- Local backup directory.

Use a backup directory that is writable by the application and is included in the store's backup and security policy.

### Step 4: Owner

Create the primary owner account:

- Owner name.
- Owner email. This is the login identifier.
- Password.
- Password confirmation.

The password must be at least 12 characters. Store it securely. There is no public default administrator password and the application does not create one automatically.

### Step 5: Review

Review all values carefully and complete setup. The first owner account is assigned the fixed Owner role and is signed in automatically. You should then:

1. Open `/status` and confirm the database and local paths are healthy.
2. Open `/settings` and confirm receipt and backup settings.
3. As the Owner, open `/access` and create individual staff accounts.
4. Create a manual backup before entering live transactions.

## 5. Login, Logout, and Access

### Login

Open `/login` and enter the email and password assigned to your account.

The system records successful and failed login events. Repeated failed attempts are throttled. If the application reports too many login attempts, wait for the displayed lockout period and confirm the email and password before trying again.

### Logout

Use **Sign Out** in the left sidebar when leaving the workstation. Always sign out before another operator uses the computer.

### Roles

The built-in roles are:

| Role | Typical responsibility |
| --- | --- |
| Owner | Full system access, setup, settings, users, roles, reports, accounting, inventory, billing, and backup controls |
| Store Manager | Daily operations, catalogue, suppliers, purchases, inventory, billing, clinical records, reports, accounting, and cash drawer |
| Pharmacist | Dispensing, catalogue, inventory receiving, customers, patients, prescriptions, controlled register, billing, and cash viewing |
| Cashier | Customer lookup, product lookup, billing, sales returns, reports, and cash drawer operations |

The Owner account cannot be disabled or stripped of its fixed Owner role from the normal access screen.

### Permission behavior

Navigation items are hidden when the signed-in user lacks the required permission. A direct attempt to open a protected page returns an authorization error. Ask an Owner or authorized Manager to review the account's role rather than sharing credentials.

Sensitive permissions include:

- Patient, prescription, doctor, and controlled-medicine register access.
- Inventory receiving and stock adjustment.
- Sales cancellation and returns.
- Accounting and reconciliation.
- User, role, settings, and backup management.

## 6. Dashboard and System Status

### Operations Dashboard: `/`

The dashboard is the daily work screen. It shows:

- Today's finalized sales amount, bills, and tax.
- Available batch count and quantity.
- Batches expiring within 30 days.
- Expired available batches.
- Supplier payable and supplier count with balances.
- Refill attention and expired-stock attention.
- Recent finalized sales.
- Recent stock movements.
- Cash drawer readiness.
- Links to reports, GST reports, backups, and system status.

Use the dashboard actions for **New Sale**, **Receive Stock**, and **Adjust Stock** when your account has the related permission.

### System Status: `/status`

System Status is a diagnostics page, not a sales dashboard. It checks:

- Application name, version, environment, and deployment mode.
- Database connection and driver.
- Offline core readiness.
- Application data path.
- Backup path, directory existence, and writability.
- Latest completed backup.
- PHP version and operating system.
- Printer readiness and available Windows printers.
- The current multi-counter release decision.

Open Status before troubleshooting. A healthy database connection does not by itself confirm that a printer or backup path is ready.

## 7. Catalogue and Products

### Recommended setup order

Before creating many products, configure reusable options at `/catalogue/masters` in this order:

1. Manufacturers.
2. Categories and optional parent categories.
3. Tax rates.
4. Units.
5. Product types.
6. Dosage forms.
7. Schedule labels.

Each option type has its own list, detail, create, edit, deactivate, and restore workflow where applicable.

### Create a product

Open `/products`, select **Add New Product**, and enter the catalogue information:

- Product or medicine name.
- SKU or internal code.
- Generic name.
- Manufacturer.
- Category.
- Product type.
- Dosage form.
- Strength or description.
- HSN code.
- Tax rate.
- Base unit and product unit conversion details.
- One or more barcodes.
- Prescription-required flag.
- Controlled-medicine flag.
- Notes and active state.

Product master data does not hold live stock. Stock is added through purchase receiving and belongs to product batches.

### Barcode entry

The product form supports:

- Manual barcode entry.
- USB keyboard-wedge scanner entry.
- Scanner focus action in the product form.

The current application does not provide camera barcode detection. Use a USB scanner or type the barcode manually. The visible system message is: **Camera barcode detection is not available. USB / Manual**.

### Product detail actions

Open a product from the product list to see its read-only detail page. Depending on permission and record state, the detail page provides:

- Edit.
- Deactivate or delete.
- Restore.
- Related product options and barcodes.

Deactivate a product when it should no longer be sold but its historical records must remain available. Do not remove a product merely because its stock is zero.

## 8. Suppliers

Open `/suppliers` to manage supplier and distributor records.

### Supplier information

Record:

- Supplier code and name.
- GSTIN and drug-licence details.
- Contact name, phone, email, and address.
- Payment terms.
- Credit limit.
- Opening balance and current outstanding balance.
- Notes and active state.

### Supplier workflow

1. Create the supplier before creating its purchase order or invoice.
2. Review the supplier detail page for profile and current balance.
3. Use the supplier ledger page to review source-linked activity.
4. Use supplier payments to record cash, bank transfer, UPI, cheque, or other settlement.
5. Use the supplier return flow from a finalized purchase invoice when goods are returned.
6. Keep payment references and notes sufficient for later reconciliation.

Supplier balances and ledgers are source-linked. Do not manually change a finalized transaction to force a balance; correct the underlying document through its supported workflow.

## 9. Purchase Orders

Open `/purchases/orders` to plan supplier purchases.

### Purchase order flow

1. Select **Add Purchase Order**.
2. Select a supplier.
3. Enter order number, reference, order date, expected date, payment terms, and notes.
4. Add product lines with unit, quantity, free quantity, cost, discount, and tax.
5. Save the draft.
6. Send the order when it is issued to the supplier.
7. Cancel or reopen the order when the supplier process changes.

Purchase orders are planning records. Saving or sending a purchase order does not add stock, create a supplier payable, or post accounting entries. Use Purchase Receiving for actual stock intake.

## 10. Purchase Receiving and Batch Inventory

### Create a purchase invoice

Open `/purchases/invoices` and select **Add Purchase Invoice**.

Enter:

- Supplier.
- Supplier invoice number and date.
- Optional purchase order link.
- Payment and tax details.
- One or more product lines.

For each product line, enter:

- Product.
- Batch number.
- Manufacturing date, when available.
- Expiry date.
- Quantity.
- Free quantity, when applicable.
- MRP.
- Purchase rate.
- Sale rate.
- Discount.
- Tax rate.

### Receiving lifecycle

1. Save the invoice as a draft.
2. Check supplier, invoice number, quantities, batch number, expiry, rates, and taxes.
3. Edit or cancel while it is still a draft.
4. Finalize only after the physical goods and invoice have been checked.
5. Finalization creates or updates product batches and writes immutable stock movements.
6. Review the invoice detail and Inventory Batches page.

Draft invoices do not change stock. Finalized invoices affect stock, accounting, supplier ledger, and reports.

### Inventory Batches: `/inventory/batches`

Use the filters to view:

- Available batches.
- Batches expiring within 90 days.
- Expired batches.
- All batches.

Each batch shows product, batch number, manufacturing date, expiry, available quantity, MRP, purchase rate, sale rate, and status.

### Stock adjustment: `/inventory/adjustments`

Use stock adjustment for a physical count difference, damage, expiry correction, theft investigation, or approved ledger correction.

1. Open **Stock Adjustments** and select **New Adjustment**.
2. Enter the adjustment date, reason, and notes or count-sheet reference.
3. Select a product batch.
4. Enter the physical counted quantity.
5. Add each changed batch as a separate line.
6. Review the ledger quantity and counted quantity.
7. Finalize only after approval.

Finalization:

- Updates the batch's available quantity.
- Creates an immutable signed stock movement.
- Creates a variance journal when the batch has a value-bearing cost.
- Stores before, counted, delta, unit cost, and value snapshots.
- Writes an audit event.

Stock adjustment requires the sensitive `inventory.adjust` permission. It is not a replacement for routine purchase receiving or sales returns.

### Purchase returns

From a finalized purchase invoice, use the return action to select the lines and quantities being returned.

- The return cannot exceed the remaining returnable quantity.
- The system checks current batch stock.
- Returned quantity creates a negative stock movement.
- Value-bearing returns create supplier credit and accounting reversal activity.
- The return is historical and remains linked to the source invoice.

## 11. Billing and Point of Sale

Open `/billing/sales` and select **New Sale**.

### Start a sale

1. Select or scan a product or batch.
2. Confirm the selected batch, expiry, available quantity, rate, tax, and discount.
3. Enter quantity.
4. Add more lines as required.
5. Optionally select a customer, patient, doctor, and prescription.
6. Select the payment method.
7. Enter the paid amount when applicable.
8. Review total, tax, discount, and change.
9. Finalize the sale.

Supported payment methods include cash, card, UPI, and mixed payment. The billing screen supports barcode or batch quick scan and walk-in billing without a customer or patient record.

### Prescription and controlled-product checks

Products marked **prescription required** or **controlled medicine** require a linked prescription and matching prescription line before finalization. A typical validation message is:

```text
Prescription-linked products require a linked prescription and prescription line.
```

Before finalizing, confirm:

- Correct patient.
- Correct doctor, when applicable.
- Correct prescription.
- Correct prescription medicine line.
- Dispensed quantity does not exceed the remaining prescribed quantity.

Finalizing a linked sale updates prescription dispensed quantity and controlled-medicine register entries where applicable.

### Hold and resume

Use **Hold** when the customer is not ready to complete payment or you must temporarily move to another bill. Use **Resume** from the held-bill list to reopen the saved sale.

Confirm the quantities and prices again after resuming before finalization.

### Finalizing a sale

Finalization:

- Writes the finalized sales invoice and line items.
- Reduces the selected batch quantity.
- Writes immutable sale stock movements.
- Posts accounting entries.
- Updates linked prescription dispensing.
- Writes controlled-medicine register entries for controlled products.
- Links cash sales to the active cash drawer shift.

Finalized sales cannot be silently deleted.

### Cancellation

Use the cancel action from the sale detail page when an entire finalized sale must be reversed. Cancellation writes reversal movements and accounting entries. It preserves the original sale and its audit history.

Do not create a second unrelated sale to hide a billing error.

### Receipt

Use **Receipt** from the finalized sale detail page to open the printable receipt view. The receipt uses store identity, paper width, copies, and footer settings saved in `/settings`.

The current receipt workflow is browser/HTML printing. A configured Windows printer can be checked from `/hardware`.

## 12. Sales Returns

Open a finalized sale and select the sales return action.

1. Select the items being returned.
2. Enter a quantity that does not exceed the remaining returnable quantity.
3. Choose whether each line should be manually restocked.
4. Confirm refund or store-credit handling according to the store procedure.
5. Finalize the return.

The system:

- Creates immutable sales return records.
- Prevents returning more than was sold or already returned.
- Restocks only lines explicitly marked for restock.
- Writes positive restock movements when restocking occurs.
- Reduces prescription dispensed quantities when the returned line was prescription-linked.
- Writes controlled-medicine reversal entries when applicable.
- Links the return to the original sale.

Inspect the return detail page after finalization.

## 13. Customers and Patients

### Customers: `/customers`

Use customer records for billing identity, contact information, GSTIN, opening balance, credit limit, outstanding balance, loyalty points, communication consent, and notes.

Customer records are optional for walk-in billing. Link a customer when the store needs an account statement, credit history, or repeated billing identity.

### Patients: `/patients`

Use patient records for:

- Patient name and code.
- Date of birth and gender.
- Phone, email, and address.
- Linked customer account.
- Primary doctor.
- Allergies.
- Medical notes.
- Communication consent.
- Prescription and billing history.

Patient data is sensitive. Only authorized users should view or edit it. Record allergies and medical notes carefully and do not treat them as a substitute for clinical judgment.

### Linking during billing

Selecting a patient can automatically fill the linked customer and primary doctor. The finalized sale preserves snapshots of the names and identifiers used at the time of billing.

## 14. Doctors and Prescriptions

### Doctor directory: `/doctors`

Record doctor name, registration number, specialization, clinic, phone, email, address, notes, and active state.

### Create a prescription

1. Open `/prescriptions` and select **New Prescription**.
2. Select the patient.
3. Select or confirm the doctor.
4. Enter prescription number, date, and optional valid-until date.
5. Add an attachment when a local image or document must be retained.
6. Add each medicine line.
7. Enter dosage instructions and prescribed quantity.
8. Optionally enter refill interval and reminder lead.
9. Save the prescription.

### Dispensing against a prescription

1. Start a sale.
2. Select the patient and prescription.
3. For a regulated product, select the matching prescription line.
4. Enter a quantity within the remaining prescribed quantity.
5. Finalize only after pharmacist review.

When a prescription quantity has been dispensed, the prescription becomes read-only so that the dispensing trail remains stable. Use a new or corrected clinical record according to store procedure instead of changing historical dispensing details.

## 15. Controlled-Medicine Register

Open `/controlled-medicines` to review the register. It is read-only and generated from audited billing events.

The register records, where available:

- Product and batch.
- Quantity effect.
- Patient.
- Doctor.
- Prescription and prescription line.
- Sale or return reference.
- Event date.
- Product, batch, patient, and document snapshots.

Automatic events include:

- Positive entry when a controlled product sale is finalized.
- Reversal entry when the sale is cancelled.
- Reversal entry when a controlled product is returned.

Do not attempt to create or edit register entries manually. Review the source sale, cancellation, or return when an entry needs investigation.

The current module is a recordkeeping foundation. Jurisdiction-specific legal print layouts, purchase registers, masked display, break-glass access, and regulator filing packs are not yet implemented.

## 16. Prescription Refills

Open `/prescriptions/refills` to review repeat-dispense work.

The tracker can show:

- Overdue lines.
- Due-soon lines.
- Pending first-fill lines.
- Upcoming lines.
- Completed lines.

Refill dates are driven by finalized dispensing history:

1. Set refill interval and reminder lead on the prescription line.
2. Finalize a sale against that line.
3. The system records the last dispensed date.
4. If prescribed quantity remains, the next due date is calculated.
5. Returns and sale cancellations recalculate the remaining dispense history.

The tracker does not send SMS, WhatsApp, or calling reminders. Staff must use the queue as a follow-up workspace.

## 17. Cash Drawer

Open `/cash-drawer` at the beginning and end of each local drawer shift.

### Open a shift

1. Enter the physical opening float.
2. Add opening notes if needed.
3. Select **Open Shift**.

Only one active local cash drawer shift is supported on the workstation.

### During the shift

- Cash sales and cash refunds are linked automatically to the open shift.
- Card, UPI, mixed, and other non-cash payments do not increase the cash drawer total.
- Record approved cash-in and cash-out movements with a reason.
- Do not use a cash movement to hide an unrecorded sale or refund.

### Close a shift

1. Count the physical drawer.
2. Review cash sales, refunds, cash-in, and cash-out.
3. Enter counted closing cash.
4. Add closing notes.
5. Select **Close Shift**.

The system calculates:

```text
Expected closing cash = opening float + cash sales + cash in - cash refunds - cash out
Variance = counted cash - expected closing cash
```

Review and sign off the variance according to store procedure. The shift detail page keeps the linked transactions and movements.

## 18. Accounting and Ledgers

Open `/accounting` for protected accounting review.

### Automatic accounting

The system posts balanced double-entry journals for supported finalized workflows, including:

- Sales.
- Sales returns.
- Sale cancellations and reversal entries.
- Purchase receipts.
- Purchase returns.
- Supplier payments.
- Stock variances.
- Card, UPI, and mixed settlement reconciliation.

Each journal links back to its source document. Journal entries are not ordinary editable notes. Correct the source document or use a supported reversal process.

### Accounting review

Review:

- Account activity.
- Debit and credit totals.
- Journal detail.
- Source document link.
- Customer statement.
- Supplier statement.
- Settlement reconciliation.

### Settlement reconciliation

Use reconciliation when card, UPI, or mixed provider settlement differs from billed payment totals.

Record:

- Provider and settlement reference.
- Period.
- Expected amount.
- Provider fee.
- Bank settlement amount.
- Receivable clearing amount.

The system prevents duplicate overlapping reconciliation periods and records an audit event.

## 19. Operational Reports and GST Working Reports

### Operational reports: `/reports`

Select a date range and review:

- Finalized sales.
- Cancellations and refunds.
- Tax and discount totals.
- Payment mix.
- Top products.
- Available inventory and quantity.
- Expired and next-expiring batches.
- Controlled-medicine register activity.
- Refill workload.
- Cash drawer activity and variance.

The controlled-medicine register has a date-bounded CSV export from the reports workspace.

### GST working report: `/reports/gst`

1. Select the start date.
2. Select the end date.
3. Review sales, sales returns, purchases, and purchase returns.
4. Review taxable value and tax by rate.
5. Review output tax, input tax, and net working amount.
6. Download the CSV when a spreadsheet handoff is needed.

The report is derived from finalized source documents. It is a GST working report, not a guarantee of statutory filing correctness or a direct government filing integration. A qualified tax professional must review the result and any jurisdiction-specific filing format.

## 20. Settings

Open `/settings` with an account that has the `settings.manage` permission. The built-in Owner role has this permission.

Settings include:

- Store identity and legal fields.
- Contact information.
- Registered pharmacist details.
- Invoice prefix.
- Financial-year start date.
- Default printer name.
- Receipt printer name.
- Receipt paper width: 58 mm or 80 mm.
- Receipt copy count.
- Receipt footer.
- Local backup path.

Save settings only after checking the effect on future receipts and backups. Configuration changes are audited.

## 21. Hardware and Printing

Open `/hardware` to review workstation readiness.

### Printer

The page shows:

- Current operating system.
- Browser printing availability.
- Configured receipt printer.
- Whether the configured printer is detected.
- Windows printers discovered on the workstation.

On Windows, an authorized user can select a printer and send a controlled test page. If the test fails:

1. Confirm the printer is powered on.
2. Confirm Windows can print a normal test page.
3. Confirm the exact Windows printer name in Settings.
4. Reopen Hardware and test again.

The normal receipt screen remains an HTML/browser print workflow. Raw printer command profiles, cash-drawer kick commands, scale integration, and device-specific hardware protocols are not part of this release.

### Barcode scanner

Most USB pharmacy scanners operate as keyboard-wedge devices. Plug the scanner into the workstation, focus the barcode field, scan, and confirm the result. A scanner normally behaves like fast keyboard input and does not require camera permissions.

Camera barcode detection is not available in the current application. Use USB scanning or manual entry.

## 22. Backups and Restore

Open `/backups` with an account that has the `settings.manage` permission. The built-in Owner role has this permission.

### Create a backup

1. Confirm the displayed backup path is correct and writable.
2. Select **Create Backup**.
3. Wait for the job to show **Completed**.
4. Confirm the file name, size, checksum, and completion time.
5. Keep the backup on protected storage according to the store's retention policy.

Backups are local SQLite database copies with recorded SHA-256 checksums. A backup job can be failed if the path is unavailable or the database cannot be exported.

### Verify a backup

At minimum, verify:

- Status is Completed.
- File exists at the displayed path.
- File size is not zero.
- Checksum is present.
- The backup directory remains accessible.

For a production recovery drill, restore a copy on a test workstation or test database rather than experimenting with the live store database.

### Restore a backup

1. Stop active billing and ensure no other operator is using the system.
2. Confirm the selected backup is Completed and available.
3. Review its date, size, and checksum.
4. Select **Restore** only after owner approval.
5. The system creates a pre-restore safety copy of the current database before replacing it.
6. Sign in again and open Status, Dashboard, reports, and a recent source document.

Restore is supported for a file-backed local SQLite database. The current system does not provide encrypted backup files, automatic offsite replication, cloud restore, or a multi-counter shared database restore workflow.

## 23. Daily Operating Procedure

### Opening checklist

1. Start the application with the correct local PHP/runtime or desktop shortcut.
2. Sign in with your own account.
3. Open `/status` and confirm database and backup readiness.
4. Confirm printer readiness from `/hardware` if receipts are required.
5. Open the cash drawer shift and count the opening float.
6. Review Dashboard attention cards for expired stock and refill work.
7. Review urgent batches approaching expiry.

### During the day

1. Receive supplier stock as a draft and check it before finalizing.
2. Bill from the correct product batch.
3. Use the prescription and patient workflow for regulated products.
4. Hold a bill rather than leaving an unrecorded counter transaction.
5. Record cash-in and cash-out with reasons.
6. Process returns from the original sale.
7. Do not use stock adjustment for a known purchase or return that should be recorded in its source module.

### Closing checklist

1. Finish or cancel held bills according to store procedure.
2. Review finalized sales, returns, and cancellations.
3. Close and reconcile the cash drawer.
4. Review report totals and unusual variances.
5. Review controlled-medicine register activity when applicable.
6. Create a backup and confirm it completed.
7. Sign out.

## 24. Weekly and Monthly Controls

### Weekly

- Review expired and soon-to-expire batches.
- Review stock adjustments and their reasons.
- Review supplier balances and unpaid invoices.
- Review cash drawer variances.
- Review failed backup jobs.
- Review refill overdue work.
- Review inactive products and suppliers.

### Monthly or according to store policy

- Reconcile purchase invoices and supplier payments.
- Review sales, returns, tax, and payment mix.
- Review GST working report with the responsible tax professional.
- Reconcile card and UPI settlement periods.
- Review controlled-medicine register entries.
- Test a backup recovery procedure.
- Review users and remove access for staff who no longer work at the store.

## 25. Troubleshooting

### The application reports PHP 8.2

Use the bundled/project-local PHP 8.3 executable:

```powershell
& .\.tools\php-8.3.33\php.exe artisan serve
```

Do not run NativePHP commands with PHP 8.2.

### The root page opens setup again

The first-run transaction is not complete or the application is using a different database. Open `/setup`, complete the wizard, and confirm the database connection on `/status`.

### A menu item is missing

The signed-in account does not have the permission required for that module. Ask the Owner or Manager to review `/access`. Do not share an Owner password.

### The page returns 403 Forbidden

The account is authenticated but lacks the required permission. This is expected protection for sensitive modules. Ask an authorized administrator to assign the correct built-in role.

### Prescription-linked product cannot be finalized

Confirm that:

- The product is marked prescription-required or controlled.
- A patient is selected when the store procedure requires it.
- A prescription is selected.
- The matching prescription line is selected.
- The sale quantity does not exceed the remaining prescribed quantity.

### Stock is unavailable

Check:

- The selected batch has available quantity.
- The batch is not expired or blocked.
- A purchase invoice was finalized.
- A previous sale, return, or adjustment did not change the balance.
- The correct product batch was selected.

Do not increase stock manually to solve a receiving error. Correct the source workflow or use an approved stock adjustment after a physical count.

### Printer is not detected

Check the exact Windows printer name, printer power, Windows print queue, and Settings. Then use `/hardware` to refresh the detected list and send a test page.

### Camera barcode scan does not work

Camera detection is not available. Use a USB keyboard-wedge scanner or enter the barcode manually.

### Backup creation fails

Open `/status` and check the backup path. Confirm the directory exists and is writable, there is enough disk space, and no process is locking the database directory. Do not delete the existing backup until a new completed backup is verified.

### A report is empty

Confirm the date range and that source documents were finalized. Draft purchases and held bills are not finalized business activity and may not appear in the relevant report.

## 26. Data Integrity and Audit Behavior

The system is designed around the following controls:

- Money and measured quantities are stored as decimal values.
- Stock workflows use database transactions.
- Stock history is represented by immutable movement rows.
- Finalized financial corrections use reversals, returns, or source-linked adjustments.
- Accounting journals must balance.
- Controlled-medicine register records are generated from billing events.
- Important setup, login, user, product, supplier, purchase, sale, return, stock, backup, hardware, and configuration actions are audited.
- Sensitive modules require explicit permissions.

An audit event is not a replacement for a written store SOP. Keep physical count sheets, supplier documents, prescription documents, approvals, and tax records according to the store's retention policy.

## 27. Current Limitations

The current release is a strong local pharmacy-management foundation, but the following are intentionally outside the present release:

- Multi-counter LAN mode and shared counter ownership.
- Multi-branch cloud synchronization.
- Online GST e-invoice, IRN, or government filing integration.
- Jurisdiction-specific GST filing packs and legal print formats.
- Automated SMS, WhatsApp, or calling reminders.
- OCR or AI extraction from prescription attachments.
- Masked-display and break-glass access workflows.
- Controlled-medicine purchase registers and regulator-specific reporting packs.
- Stock verification sheets, recall blocking, and advanced low-stock alerting.
- Raw printer command profiles, cash-drawer kick commands, scale integration, and customer displays.
- Encrypted or cloud backup replication.
- Signed commercial installer and automatic update distribution.

Do not promise these functions to a store user as if they are already available.

## 28. Key URLs

| Task | URL |
| --- | --- |
| Dashboard | `/` |
| Setup | `/setup` |
| Login | `/login` |
| System Status | `/status` |
| Products | `/products` |
| Product Options | `/catalogue/masters` |
| Suppliers | `/suppliers` |
| Purchase Orders | `/purchases/orders` |
| Purchase Receiving | `/purchases/invoices` |
| Inventory Batches | `/inventory/batches` |
| Stock Adjustments | `/inventory/adjustments` |
| Billing | `/billing/sales` |
| Sales Returns | Available from a finalized sale detail page |
| Customers | `/customers` |
| Patients | `/patients` |
| Doctors | `/doctors` |
| Prescriptions | `/prescriptions` |
| Refill Tracker | `/prescriptions/refills` |
| Controlled Medicines | `/controlled-medicines` |
| Cash Drawer | `/cash-drawer` |
| Accounting | `/accounting` |
| Reports | `/reports` |
| GST Working Reports | `/reports/gst` |
| Hardware | `/hardware` |
| Backups | `/backups` |
| Access | `/access` |
| Settings | `/settings` |

## 29. Administrator Quick Reference

### Safe deployment sequence

1. Confirm PHP 8.3+ and required dependencies.
2. Configure `.env` and the local database.
3. Run migrations.
4. Build local frontend assets.
5. Open `/setup` and create the owner account.
6. Confirm `/status` and `/hardware`.
7. Create a completed backup.
8. Create staff accounts with least privilege.
9. Configure products, units, tax rates, suppliers, and printers.
10. Run a test receiving, test sale, test return, test report, and test backup on the target workstation.

### Development verification commands

```powershell
& .\.tools\php-8.3.33\php.exe artisan migrate --force
& .\.tools\php-8.3.33\php.exe artisan test
npm run build
& .\.tools\php-8.3.33\php.exe C:\composer\composer.phar validate --no-check-publish
```

Use production backups before migrations or upgrades. Never run destructive database commands against the live store database without an approved recovery plan.

## 30. Support Information to Collect

When reporting a problem, record:

- Store code.
- Application version from `/status`.
- Operating system.
- PHP version when running browser development mode.
- Exact screen and action.
- Date and time.
- Document number, if applicable.
- Screenshot or exact validation message.
- Whether the issue affects one user or all users.
- Whether the latest backup is completed.

Do not send passwords, `.env` files, database files, patient records, prescription attachments, or full controlled-medicine exports through an unsecured channel.
