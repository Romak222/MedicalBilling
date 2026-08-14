# Development Roadmap

## Phase 1: Architecture and Project Foundation

- Laravel foundation
- SQLite development setup
- Livewire, Alpine, Tailwind shell
- Status screen
- Documentation
- Initial tests
- NativePHP compatibility check

## Phase 2: NativePHP Desktop Setup

- Add project-local PHP 8.3+
- Install `nativephp/desktop`
- Run `native:install`
- Configure main 1366 x 768 desktop window
- Verify `native:run`
- Document Windows build prerequisites

Status: complete with packaging caveats. NativePHP is installed, the desktop window opens the app root, development run was verified, and a Windows unpacked app was produced. Final installer hardening remains a release task.

## Phase 3: First-run Wizard

- Store profile
- GSTIN/PAN/drug licence fields
- Registered pharmacist fields
- Invoice prefix and financial year
- Printer and backup paths
- Owner account creation

Status: complete. Setup writes its records transactionally and creates no public default account.

## Phase 4: Authentication, Roles, Permissions

- Local login
- Role and permission models
- Sensitive action authorization
- Audit hooks

Status: complete. The app has local login/logout, owner role assignment, foundation permissions, protected status routes, login-event logging, and audit-event hooks.

## Phase 5: Product and Medicine Catalogue

- Product master
- Units and conversions
- GST rates and HSN
- Barcode records
- Prescription and controlled flags

Status: complete. Catalogue master records, a protected `/products` workspace, owner permissions, create/edit/delete/restore actions, and audit hooks are implemented without stock or billing behavior.

## Phase 6: Suppliers and Purchase Management

- Supplier profiles
- Purchase order
- Purchase invoice
- Purchase returns
- Supplier ledger

Status: complete for the current purchase-management foundation. Supplier profiles, contacts, terms, balances, protected routes, CRUD screens, purchase orders, purchase order items, order status transitions, purchase receiving, bounded purchase returns, supplier payment posting, source-linked supplier ledger entries, supplier statement pages, journal links, and audit hooks are implemented. Stock adjustment and GST working-report foundations are now implemented; jurisdiction-specific filing packs and release hardening remain later work.

## Phase 7: Batch Inventory and Stock Ledger

- Product batches
- Immutable stock movements
- FEFO selection
- Expiry and low-stock alerts

Status: in progress. Purchase receiving now creates batch records with expiry, MRP, purchase rate, sale rate, available quantity, and immutable stock movement intake. FEFO sales consumption and sensitive stock adjustments are implemented. Stock verification sheets, recalls, and low-stock alerts remain pending.

## Phase 8: Pharmacy POS and Sales

- Keyboard-first billing
- Batch selection
- Payments
- Hold/resume bills
- Thermal receipt

Status: in progress. Sales invoices, batch selection, barcode/batch quick scan, hold/resume, printable receipt view, payment method/paid/change fields, stock consumption movements, cancellation reversal, sales returns with optional manual restock, linked customer/patient records, active cash-drawer shift linkage, and automatic sales journal postings are implemented. Printer discovery/test readiness and GST working reports are implemented; device-specific printer profiles and statutory filing packs remain pending.

## Phase 9: Doctors and Prescriptions

- Doctor directory
- Patient-to-doctor linkage
- Prescription register and attachment storage
- Prescription-linked dispensing validation

Status: in progress. Doctor masters, patient primary-doctor linkage, prescription headers and line items, local attachment storage, prescription-linked billing, and dispensed-quantity tracking are implemented. Controlled-drug statutory registers, masked-display workflows, and compliance reporting remain pending.

## Phase 10: Controlled Medicines

- Controlled-medicine register
- Bill-linked compliance entries
- Reversal entries for cancellation and returns
- Refill scheduling and repeat-dispense reminders

Status: in progress. A read-only controlled-medicine register, automatic entry creation from finalized billing, reversal entry creation from bill cancellation and sales returns, a prescription refill tracker with auto-updated next-due dates, a cash-drawer shift ledger with close variance, protected customer/supplier sub-ledgers, card/UPI/mixed settlement reconciliation, a posted accounting journal review workspace, a real operations dashboard, separate system diagnostics, GST working reports, database backup/restore, and hardware readiness are implemented. Statutory filing formats, masked-display workflows, recall controls, and broader compliance reporting remain pending.

## Later Phases

11. Advanced controlled-drug and compliance records
12. Deeper accounting and cash controls: journal foundation, customer/supplier sub-ledgers, settlement reconciliation, purchase returns, and supplier payments implemented
13. Jurisdiction-specific GST filing packs
14. Advanced business reports and dashboards: the operational dashboard and diagnostics page are implemented; deeper analytics remain later work
15. Backup and restore: local SQLite backup/restore foundation implemented; encrypted rotation and disaster-recovery operations remain later work
16. Device-specific printing and hardware integration: printer readiness and test-page foundation implemented; raw device profiles remain later work
17. Multi-counter LAN mode remains intentionally deferred and is not part of the current release
18. Online outbox and optional integrations
19. Cloud synchronization and multi-branch mode
20. Licensing, installer, signing, and production release
