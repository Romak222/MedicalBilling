# Billing

Phase 8 adds POS billing foundation.

## Routes

- `/billing`
- `/billing/sales`
- `/billing/sales/create`
- `/billing/sales/{salesInvoice}`
- `/billing/sales/{salesInvoice}/receipt`
- `/billing/sales/{salesInvoice}/returns/create`
- `/billing/returns/{salesReturn}`

Billing routes require first-run setup and login. Viewing sales invoices and returns requires `sales.view`. Creating bills, returns, and bill cancellation requires `sales.manage`.

## Implemented Records

- `sales_invoices`
- `sales_invoice_items`
- `held_sales_bills`
- `sales_returns`
- `sales_return_items`
- `cash_drawer_shift_id` links on sales invoices and sales returns

Sales invoices store invoice number, date, optional linked customer, patient, doctor, and prescription IDs, customer/patient/doctor snapshot fields, prescription number snapshot, status, subtotal, discount, tax, total, payment method, paid amount, change amount, and finalized/cancelled metadata.

Sales invoice items store product and batch snapshots, expiry snapshot, optional linked prescription item, quantity, unit price, discount, tax rate, and line totals.

Held bills store the current cart payload plus customer and patient header fields. Holding a bill does not reserve or reduce stock.

Sales returns store the source bill, return number/date, refund method, refund amount, notes, and finalized metadata.

Sales return items store the original sold line link, returned quantity, refund totals, and whether that returned pack was manually approved for restock.

## Counter Flow

- Scan a product barcode or type a batch number into quick scan.
- Select an existing customer or patient record when needed, or continue as walk-in.
- Select a doctor or prescription when the bill uses clinical linkage.
- The app selects the earliest expiring available batch for that product.
- Prescription-required or controlled products must be matched to a prescription and prescription line before finalization.
- Add or adjust line quantity, price, discount, and tax.
- Hold the bill when the customer pauses.
- Resume a held bill later and finalize it.
- Print the receipt from the bill detail page.
- Open a finalized bill and process a full or partial return.
- Restock only the lines that were physically checked and marked safe to return to saleable stock.

## Stock Behavior

Finalizing a bill:

1. Requires a non-expired, unblocked batch with available quantity.
2. Reduces `product_batches.available_quantity`.
3. Writes a negative `stock_movements` row with movement type `sale`.
4. Increments dispensed quantity on linked prescription lines and re-syncs prescription status.
5. Writes a controlled-medicine register entry for controlled products.

Cancelling a finalized bill:

1. Marks the invoice cancelled.
2. Restores the batch quantity.
3. Writes a positive `stock_movements` row with movement type `sale_cancel`.
4. Reverses dispensed quantity on linked prescription lines and re-syncs prescription status.
5. Writes a controlled-medicine register reversal entry for controlled products.

Finalizing a sales return:

1. Requires a finalized source bill and item-level quantities that do not exceed the remaining sold quantity.
2. Creates immutable `sales_returns` and `sales_return_items` records.
3. Restores stock only for return lines marked for manual restock.
4. Writes a positive `stock_movements` row with movement type `sale_return_restock` only for restocked lines.
5. Reduces dispensed quantity on linked prescription lines for the returned quantity.
6. Writes a controlled-medicine register reversal entry for controlled products.

Sales records and stock movement history are not silently deleted.

## Cash Drawer Linkage

When a shift is open, finalized cash invoices and cash refunds automatically store its `cash_drawer_shift_id`. Card, UPI, mixed, and other non-cash methods do not affect the drawer totals. The billing form shows the active shift or warns when cash activity would remain unassigned.

## Boundary

Phase 8 billing now supports controlled-medicine register and cash-drawer linkage foundations, automatic accounting postings, GST working-report source data, browser printing, and printer readiness checks. Device-specific printer profiles, raw hardware protocols, and jurisdiction-specific filing packs remain separate future work.
