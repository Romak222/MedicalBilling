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

Each supplier can have contacts. The current UI manages one primary contact and links the supplier to purchase invoices, purchase returns, supplier payments, and the protected payable statement.

## Audit Events

- `supplier.created`
- `supplier.updated`
- `supplier.deactivated`
- `supplier.restored`

Delete actions deactivate records instead of removing rows. This preserves supplier identity for future purchase, payment, ledger, and audit history.

## Boundary

The Phase 6 supplier-directory screens do not themselves create purchase orders, purchase invoices, product batches, stock movements, or POS behavior. Current accounting workflows add these supplier-linked routes:

- `/suppliers/{supplier}/ledger`
- `/suppliers/{supplier}/payments`
- `/suppliers/{supplier}/payments/create`
- `/suppliers/{supplier}/payments/{supplierPayment}`

Supplier statements are derived from immutable source-linked ledger entries. Finalized purchase receipts increase the payable; purchase returns and supplier payments reduce it. Financial records are posted through balanced journals and are not silently deleted.
