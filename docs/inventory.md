# Inventory

Phase 7 adds batch inventory and purchase receiving.

## Routes

- `/purchases/invoices`
- `/purchases/invoices/create`
- `/purchases/invoices/{purchaseInvoice}`
- `/purchases/invoices/{purchaseInvoice}/edit`
- `/inventory/batches`

Receiving routes require first-run setup and login. Viewing receiving records requires `purchases.view`. Creating and editing draft receiving records requires `purchases.manage`. Finalizing receiving requires `inventory.manage`. Viewing batch stock requires `inventory.view`.

## Implemented Records

- `purchase_invoices`
- `purchase_invoice_items`
- `product_batches`
- `stock_movements`

## Product Expiry Design

Expiry is intentionally not stored on the product master. A product can have many batches, and each batch has its own batch number, expiry, MRP, purchase rate, sale rate, and available quantity.

## Receiving Flow

1. Create a purchase invoice draft.
2. Select supplier and optional purchase order.
3. Add product lines with batch number, MFG date, expiry date, quantity, free quantity, MRP, purchase rate, sale rate, discount, and tax.
4. Save draft.
5. Edit or cancel while still draft.
6. Finalize invoice.
7. Finalization creates or updates product batches.
8. Finalization writes immutable `stock_movements` rows.

Draft invoices do not affect stock.

## Inventory Flow

The Inventory Batches page shows batches with available quantity and expiry status:

- Available
- Expiring within 90 days
- Expired
- All batches

## Boundary

The initial Phase 7 receiving slice handled purchase intake only. The current system also consumes and reverses stock through billing and returns, and links finalized purchase receipts, purchase returns, and supplier payments to accounting and supplier ledgers. Stock adjustment, stock verification, recall blocking, and GST reports remain pending.
