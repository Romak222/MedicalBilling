# Architecture

## Goal

Build an installable Windows desktop pharmacy operating system for independent pharmacies, multi-counter stores, and later multi-branch chains.

The product must work offline for billing, stock, purchase, accounts, reports, backup, and restore. Online integrations are optional modules and must never block local billing.

## Current Foundation

- Laravel 12.x application
- NativePHP Desktop 2.2 installed
- SQLite local development database
- Blade, Livewire, Livewire-bundled Alpine, Tailwind CSS
- PHPUnit tests
- Status screen at `/status`
- First-run setup wizard at `/setup`
- Local authentication and owner-role access control
- Audit records for setup and authentication events
- Product and medicine catalogue master data
- Supplier and distributor directory foundation
- Purchase order foundation
- Purchase receiving and batch inventory foundation
- POS billing foundation
- Customer and patient directory foundation
- Doctor and prescription workflow foundation
- Controlled-medicine register foundation
- Cash-drawer shift and reconciliation foundation
- Architecture documentation for future phases

## Compatibility Decision

Laravel 13 and NativePHP Desktop v2 both require PHP 8.3+. The global machine PHP is 8.2.12, so Phase 1 used Laravel 12. Phase 2 added a project-local PHP 8.3.33 runtime under `.tools` and installed NativePHP Desktop 2.2. Laravel 12 remains inside NativePHP Desktop's supported Laravel range.

## Deployment Modes

### Mode A: Single Computer

- NativePHP Windows application
- Local SQLite database
- Local printer and barcode peripherals
- Automatic backup to a configured local or external path

### Mode B: Multi-counter LAN

- One local server computer hosts MySQL or MariaDB
- Counter machines run the desktop app and connect over LAN
- Stock updates use transactions and row-level locking where supported
- SQLite files are never shared over a network drive

### Mode C: Multi-branch Hybrid

- Each branch continues locally during internet failure
- Local transactions sync to cloud later
- Every transaction has a stable globally unique identifier
- Sync is idempotent and conflict-aware

## Application Layers

- Presentation: Blade, Livewire, Alpine, Tailwind
- HTTP and desktop entry: Laravel routes and future NativePHP windows
- Application services/actions: transactional workflows
- Domain records: products, batches, stock movements, sales, purchases, accounts, audits
- Infrastructure: database, filesystem, backup, printing, outbox, hardware bridge

## Data Paths

Development uses `database/database.sqlite`.

Packaged desktop builds must store production data under the Windows application-data directory, not the replaceable installation directory. The current status service resolves a default application data path from `LOCALAPPDATA` on Windows and falls back to `storage/app/pharmacy-data`.

NativePHP's main window is configured in `app/Providers/NativeAppServiceProvider.php` and opens the root route with desktop-first dimensions. Fresh installs redirect from `/` to `/setup` until setup completion is recorded.

## First-run Setup

Phase 3 creates the foundation records needed before regulated pharmacy workflows are introduced:

- `stores`
- `registered_pharmacists`
- `application_settings`
- `first_run_setup_steps`
- owner-user flags on `users`

Setup completion writes store, pharmacist, billing defaults, printer/backup defaults, owner account, and step markers in a single database transaction. No medicine, purchase, stock, sales, prescription, GST reporting, or accounting tables are introduced in this phase.

## Access Control and Audit

Phase 4 protects the dashboard/status routes after setup completion. The owner user created during setup receives the `owner` role and the foundation permission set. Login attempts are stored in `login_events`, while setup completion, login success/failure, and logout are stored in `audit_events`.

The first permission boundary is `system.status.view`. Future modules must add their own permission slugs when their data and workflows are introduced.

## Catalogue Boundary

Phase 5 adds catalogue master data for products, medicine flags, manufacturers, categories, tax rates, units, and barcodes. It intentionally does not add stock balances, batches, suppliers, purchases, sales, prices, or POS workflows.

## Supplier Boundary

Phase 6 adds supplier and distributor profiles, primary contacts, drug-licence metadata, payment terms, credit limits, and profile balances. Supplier profiles remain separate from purchase invoices, supplier payments, supplier ledgers, stock batches, stock movements, accounting entries, and POS workflows.

## Purchase-Order Boundary

Phase 6 adds purchase orders and purchase order items as planning/request records. It intentionally does not add purchase invoices, receiving, batch creation, stock movements, supplier payments, supplier ledgers, accounting entries, or POS workflows.

## Batch Inventory Boundary

Phase 7 adds purchase invoices, product batches, and purchase-receiving stock movements. Expiry belongs to product batches, not product master records. Finalizing a purchase invoice creates or updates batches and records immutable stock movement rows. The current accounting foundation also adds bounded purchase returns, supplier payments, supplier ledger entries, and journal postings. Stock adjustments and GST reporting remain pending.

## Billing Boundary

Phase 8 adds sales invoices and sales invoice items with optional linked customer and patient records. Finalized bills consume batch inventory with immutable sale stock movements. Cancelling a bill writes reversal stock movements instead of deleting history. HTML receipt printing and sales returns with optional manual restock are implemented.

## Clinical Workflow Boundary

Phase 9 adds doctor masters, patient primary-doctor linkage, prescription headers, prescription line items, local attachment storage, and prescription-linked dispensing checks. Billing can now enforce prescription selection for flagged products and tracks dispensed quantity against prescription lines. Controlled-drug registers, accounting entries, GST reports, and hardware-level printer integration remain pending.

## Controlled-Medicine Boundary

Phase 10 adds a read-only controlled-medicine register driven from finalized sales, sales cancellation, and sales returns, refill-tracked prescription-line timing fields and a refill review workspace, and a cash-drawer shift ledger with active cash sale/refund linkage and close variance. The register, refill tracker, and drawer ledger store audited context derived from billing history, but statutory exports, masked-display workflows, accounting journals, and regulator-specific reporting remain pending.

## Transaction Rules

- Stock, purchase, sale, return, accounting, and audit updates must be atomic.
- Stock availability should be derived from immutable stock movements plus validated projections.
- Financial transaction corrections should use reversal documents.
- Sensitive changes require authorization and audit entries.

## Online Boundaries

Online services such as WhatsApp, SMS, GST e-invoice, payment gateways, cloud backup, catalogue updates, and distributor integrations are future optional modules. They must write pending work to an outbox and retry safely.
