# Access Control

Phase 4 adds the local access-control foundation.

## Authentication

- `/login` accepts local owner credentials after first-run setup is complete.
- `/logout` ends the local session and records an audit event.
- `/` and `/status` redirect to `/setup` before setup completion, then require login.

## System Role

The initial system role is `owner`. It is assigned to the first owner account created during setup. A Phase 4 data migration also assigns this role to existing users marked `is_owner`.

## Foundation Permissions

- `system.status.view`
- `setup.manage`
- `settings.manage`
- `users.manage`
- `roles.manage`
- `audit.view`
- `sensitive.approve`
- `catalogue.view`
- `catalogue.manage`
- `tax_rates.manage`
- `suppliers.view`
- `suppliers.manage`
- `customers.view`
- `customers.manage`
- `patients.view`
- `patients.manage`
- `doctors.view`
- `doctors.manage`
- `prescriptions.view`
- `prescriptions.manage`
- `controlled_medicines.view`
- `purchases.view`
- `purchases.manage`
- `inventory.view`
- `inventory.manage`
- `sales.view`
- `sales.manage`

These permissions are intentionally limited to foundation, catalogue, supplier-directory, customer/patient, doctor/prescription, controlled-medicine register, purchase-order, purchase-receiving, inventory-batch, and POS billing modules. Supplier-ledger, accounting, advanced compliance exports, and advanced health-data permissions will be added with their respective modules.

## Audit

Phase 4 records:

- `setup.completed`
- `auth.login.succeeded`
- `auth.login.failed`
- `auth.logout`
- `catalogue.product.created`
- `catalogue.product.updated`
- `catalogue.product.deactivated`
- `catalogue.product.restored`
- `supplier.created`
- `supplier.updated`
- `supplier.deactivated`
- `supplier.restored`
- `purchase_order.created`
- `purchase_order.updated`
- `purchase_order.sent`
- `purchase_order.cancelled`
- `purchase_order.reopened`
- `purchase_invoice.created`
- `purchase_invoice.updated`
- `purchase_invoice.cancelled`
- `purchase_invoice.finalized`
- `customer.created`
- `customer.updated`
- `customer.deactivated`
- `customer.restored`
- `patient.created`
- `patient.updated`
- `patient.deactivated`
- `patient.restored`
- `doctor.created`
- `doctor.updated`
- `doctor.deactivated`
- `doctor.restored`
- `prescription.created`
- `prescription.updated`
- `prescription.archived`
- `prescription.restored`
- `sales_invoice.created`
- `sales_invoice.cancelled`
- `sales_return.created`

Audit records are stored locally in `audit_events`. Login attempts are also stored in `login_events`.
