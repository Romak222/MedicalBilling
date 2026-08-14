# Cash Drawer Shifts

Phase 10 adds a local cash-drawer shift ledger for counter handover and end-of-shift reconciliation.

## Routes

- `/cash-drawer`
- `/cash-drawer/{cashDrawerShift}`

Viewing the workspace requires `cash_drawer.view`. Opening and closing shifts or recording manual cash movements requires `cash_drawer.manage`.

## Implemented Records

- `cash_drawer_shifts`
- `cash_drawer_entries`
- `sales_invoices.cash_drawer_shift_id`
- `sales_returns.cash_drawer_shift_id`

A shift records its opening float, opening and closing notes, opening/closing operators, cash sales, cash refunds, manual cash-in, manual cash-out, expected closing cash, counted cash, and signed variance.

Manual cash movements require a positive amount and a reason. Each movement is immutable in the operational UI and creates an audit event.

## Counter Flow

1. Open `/cash-drawer` and enter the physical opening float.
2. Finalize cash bills while the shift is open. The active shift is attached automatically to cash invoices.
3. Finalize cash refunds while the shift is open. The active shift is attached automatically to cash returns.
4. Record cash-in or cash-out movements with a reason for safe transfers, petty cash, or other approved drawer activity.
5. Count the physical drawer, enter the counted amount, and close the shift.
6. Review the expected amount, counted amount, signed variance, linked cash bills, refunds, and manual movements from the shift detail page.

Card, UPI, mixed, and other non-cash payments do not change the drawer total. Cash bills finalized without an active shift remain visible as unassigned cash activity and should be corrected operationally before production use.

## Calculation

`expected closing cash = opening float + cash sales + cash in - cash refunds - cash out`

All persisted money values use decimal columns. Reconciliation arithmetic is performed in integer cents inside the service so floating-point rounding does not affect the variance.

## Boundary

This is a cash-control foundation, not the accounting ledger. Journal entries, payment allocations, supplier/customer ledgers, bank reconciliation, GST reporting, multi-counter device ownership, and hardware cash-drawer triggers remain separate future work.

The local demo chain creates closed shift `CD-DEMO-001` with a `-5.00` variance plus demo cash-in and cash-out movements when an owner account exists.
