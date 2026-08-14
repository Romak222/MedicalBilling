# Database Plan

This document is the long-range database plan. The current foundation has implemented first-run foundation, access-control, audit-login, catalogue master tables, supplier profile tables, purchase order tables, purchase invoice receiving tables, product batches, stock movements, stock adjustment documents, POS billing tables, customer, patient, doctor, prescription, controlled-medicine register tables, prescription refill timing fields, cash-drawer shift tables, sales return tables, accounting journals, customer/supplier sub-ledgers, payment reconciliations, purchase returns, supplier payments, and backup job records. GST working reports are derived from source documents without a separate tax ledger; jurisdiction-specific statutory reporting and advanced stock verification remain future phases.

## Database Targets

- Single computer: SQLite
- Multi-counter LAN: MySQL or MariaDB
- Multi-branch hybrid: local branch database plus future cloud database

Migrations must stay SQLite-compatible and portable to MySQL/MariaDB.

## Core Principles

- Use foreign keys and indexes.
- Use decimal for money and quantities.
- Store invoice headers separately from invoice items.
- Store product master data separately from batches.
- Track stock through immutable stock movements.
- Track payments separately from invoices.
- Use database transactions for every stock and accounting workflow.
- Soft delete only where legally and operationally appropriate.
- Reverse finalized financial records instead of deleting them.
- Add `created_by`, `updated_by`, store/device identifiers, and audit metadata where appropriate.

## Proposed Table Groups

### Foundation

- stores
- application_settings
- first_run_setup_steps
- registered_pharmacists
- devices
- backup_jobs
- outbox_messages

Phase 3 implemented `stores`, `registered_pharmacists`, `application_settings`, and `first_run_setup_steps`, plus setup flags on `users`.

### Access

- users
- roles
- permissions
- role_user
- permission_role
- approval_requests
- login_events

Phase 4 implemented `roles`, `permissions`, `role_user`, `permission_role`, and `login_events`.

### Catalogue

- products
- product_units
- product_barcodes
- product_images
- product_alternatives
- manufacturers
- categories
- tax_rates

Phase 5 implemented `manufacturers`, `categories`, `tax_rates`, `products`, `product_units`, and `product_barcodes`. Product images and alternatives remain pending.

### Suppliers and Purchases

- suppliers
- supplier_contacts
- purchase_orders
- purchase_order_items
- purchase_invoices
- purchase_invoice_items
- purchase_returns
- purchase_return_items
- supplier_payments
- supplier_ledger_entries

Phase 6 implemented `suppliers`, `supplier_contacts`, `purchase_orders`, and `purchase_order_items`. Phase 7 implemented `purchase_invoices`, `purchase_invoice_items`, `product_batches`, and `stock_movements`. The current accounting foundation adds `purchase_returns`, `purchase_return_items`, `supplier_payments`, and source-linked supplier ledger entries and journal postings for finalized returns and payments.

### Inventory

- product_batches
- stock_locations
- stock_movements
- stock_adjustments
- stock_verifications
- expiry_blocks
- recalls

Phase 7-8 implemented `product_batches` and `stock_movements` for purchase receiving, sales consumption, bill cancellation reversal, optional sales-return restock, and protected stock adjustments. Stock locations, verification sheets, expiry blocks, recalls, and advanced alerting remain pending.

### Sales

- customers
- patients
- doctors
- prescriptions
- sales_invoices
- sales_invoice_items
- sale_payments
- sales_returns
- sales_return_items
- cashier_shifts

Phase 8-10 implemented `customers`, `patients`, `doctors`, `prescriptions`, `prescription_items`, `sales_invoices`, `sales_invoice_items`, `held_sales_bills`, `sales_returns`, `sales_return_items`, `cash_drawer_shifts`, and `cash_drawer_entries`. Sales invoices now support optional `customer_id`, `patient_id`, `doctor_id`, `prescription_id`, and line-level `prescription_item_id` linkage while preserving customer, patient, doctor, and prescription snapshot fields on the bill. `prescription_items` now also carry refill interval, reminder lead, last-dispensed, and next-due timing fields derived from billing history. Cash invoices and cash refunds can link to an active drawer shift. Customer store-credit return entries are recorded in `customer_ledger_entries`; payment-detail records and multi-counter cashier ownership remain pending.

### Regulated Records

- controlled_medicine_register_entries
- prescription_dispensations
- controlled_medicine_registers
- pharmacist_verifications
- compliance_events

Phase 10 implements `controlled_medicine_register_entries` as an automatic, bill-linked register foundation for controlled-product sales, cancellations, and sales returns. Broader statutory filing exports, masked-display workflows, recall controls, and reporting packs remain pending.

### Accounts

- accounts
- journal_entries
- journal_entry_lines
- vouchers
- customer_ledger_entries
- supplier_ledger_entries
- payment_reconciliations

Phase 10 implemented `accounts`, `journal_entries`, and `journal_entry_lines` with a configurable chart of accounts, balanced double-entry validation, source-document linkage, cancellation reversal entries, and automatic postings for sales, returns, purchase receipts, purchase returns, supplier payments, and stock variances. `customer_ledger_entries`, `supplier_ledger_entries`, and `payment_reconciliations` now provide source-linked customer/supplier statements and card/UPI/mixed settlement posting. GST working reports are derived from source documents; vouchers, filing-specific tax ledgers, and regulator-specific tax reporting remain pending.

### Audit

- audit_events
- audit_event_changes
- negative_stock_attempts
- price_change_events
- discount_approval_events
- backup_restore_events

Phase 4 implemented `audit_events`.

## Stock Movement Proposal

`stock_movements` should include:

- immutable movement identifier
- source document type and id
- store, location, product, and batch
- movement type
- signed quantity
- unit conversion metadata
- cost basis where allowed
- created_by and device_id
- occurred_at

Available stock is computed from movements and cached projections. Projections can be rebuilt from the ledger.

## Indexing Plan

Add indexes for:

- product name, generic name, composition, manufacturer
- barcode and custom QR code
- batch number and expiry date
- invoice number and transaction date
- supplier/customer outstanding lookup
- stock movement source and product/batch lookup
- audit event actor, subject, and timestamp

## Portability Notes

- Avoid database-specific enum behavior in migrations.
- Use string-backed status columns plus validation/constants in code.
- Keep JSON columns limited to metadata that does not need relational querying in SQLite.
- Use explicit decimal precision and scale for money and quantities.
