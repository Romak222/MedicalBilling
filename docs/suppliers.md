# Suppliers

Phase 6 adds the supplier and distributor directory foundation.

## Routes

- `/suppliers`
- `/suppliers/create`
- `/suppliers/{supplier}`
- `/suppliers/{supplier}/edit`

Supplier routes require first-run setup and login. Viewing supplier records requires `suppliers.view`. Creating, editing, deleting/deactivating, and restoring supplier records requires `suppliers.manage`.

## Implemented Records

- `suppliers`
- `supplier_contacts`

Supplier profile fields include supplier name, supplier code, GSTIN, drug-licence number and validity date, address, phone, email, payment terms, opening balance, credit limit, outstanding balance, active status, notes, and created/updated user references.

Each supplier can have contacts. The Phase 6 UI manages one primary contact so operators have an immediate person, phone, and email available before purchase workflows are introduced.

## Audit Events

- `supplier.created`
- `supplier.updated`
- `supplier.deactivated`
- `supplier.restored`

Delete actions deactivate records instead of removing rows. This preserves supplier identity for future purchase, payment, ledger, and audit history.

## Boundary

The supplier foundation does not create purchase orders, purchase invoices, supplier payments, supplier ledgers, product batches, stock movements, accounting entries, or POS behavior.
