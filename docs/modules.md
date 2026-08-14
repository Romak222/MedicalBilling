# Modules

The system will be built module by module.

## Modules Created

- Status screen
- Configuration foundation
- Local SQLite development setup
- Documentation set
- Initial test coverage
- NativePHP desktop shell
- First-run setup wizard
- Store profile foundation
- Registered pharmacist foundation
- Application settings foundation
- Owner account creation
- Local login and logout
- Role and permission foundation
- Audit and login event foundation
- Product and medicine catalogue foundation
- Manufacturer, category, tax-rate, unit, and barcode records
- Supplier and distributor directory foundation
- Supplier contacts, credit terms, and profile balances
- Purchase order foundation with line-item snapshots and status flow
- Purchase receiving foundation
- Batch inventory with expiry tracking and immutable stock movement intake
- POS billing foundation with batch stock consumption and cancellation reversal
- Customer and patient foundation with billing linkage and restricted notes
- Doctor and prescription foundation with dispensing linkage
- Controlled-medicine register foundation with automatic reversal logging
- Prescription refill tracker with due-date recalculation from billing history
- Cash drawer shift ledger with cash movement audit and close variance

## Planned Product Modules

1. Installation and first-run setup
2. User and access management
3. Medicine and product catalogue
4. Suppliers and distributors
5. Purchase management
6. Batch-wise inventory and stock ledger
7. Pharmacy POS and sales billing
8. Returns and refunds
9. Doctors and prescription workflows
10. Controlled-medicine and compliance foundations
11. Accounts and cash management
12. GST and taxation
13. Reports and dashboards
14. Backup and recovery
15. Audit and security
16. Hardware integration
17. Multi-counter LAN mode
18. Online outbox and optional integrations
19. Cloud sync, licensing, installer, signing, and release

## Module Boundaries

- Catalogue stores master product data, not stock balances.
- Suppliers store profile, contact, licence, and terms data, not purchase documents or ledger postings yet.
- Purchase orders store request/planning records only; invoices, receiving, ledger postings, and stock movements remain separate future workflows.
- Inventory stores batches and immutable movements.
- Billing consumes batch stock through movements and must not edit product master stock.
- Customer, patient, doctor, and prescription records store reusable identity and clinical linkage, while finalized bills preserve snapshot values for auditability.
- Prescription dispensing tracks prescribed and dispensed quantity by line.
- Controlled-medicine register entries are generated from real billing events and are not manually edited.
- Cash drawer shifts reconcile opening float, cash sales, cash refunds, manual cash movements, counted cash, and variance; accounting journals remain separate.
- Sales and purchases create source documents and movements in one transaction.
- Accounts stores financial ledgers traceable to source documents.
- Audit stores immutable security and operational events.
- Online integrations consume outbox messages and never own core transactions.
