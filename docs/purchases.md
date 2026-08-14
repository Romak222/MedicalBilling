# Purchases

Phase 6 adds the purchase-order foundation.

## Routes

- `/purchases`
- `/purchases/orders`
- `/purchases/orders/create`
- `/purchases/orders/{purchaseOrder}`
- `/purchases/orders/{purchaseOrder}/edit`

Purchase routes require first-run setup and login. Viewing purchase order records requires `purchases.view`. Creating, editing, sending, cancelling, and reopening purchase orders requires `purchases.manage`.

## Implemented Records

- `purchase_orders`
- `purchase_order_items`

Purchase order headers store the supplier link, supplier name snapshot, order number, reference number, ordered date, expected date, payment terms, status, subtotal, discount, tax, total, notes, sent/cancelled metadata, and created/updated user references.

Purchase order items store product links, product name snapshots, unit name, quantity, free quantity, unit cost, discount, tax rate, line subtotal, line tax, line total, and notes.

## Status Flow

- Draft
- Sent
- Cancelled
- Reopened to draft

Cancellation is a status transition. The app does not silently delete purchase order records.

## Audit Events

- `purchase_order.created`
- `purchase_order.updated`
- `purchase_order.sent`
- `purchase_order.cancelled`
- `purchase_order.reopened`

## Boundary

Purchase orders are planning/request documents only. They do not receive stock, create product batches, write stock movements, create purchase invoices, post supplier ledger entries, record supplier payments, or create accounting entries.
