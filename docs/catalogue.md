# Catalogue

Phase 5 adds the product and medicine catalogue foundation.

## Route

- `/products`
- `/products/create`
- `/products/{product}`
- `/products/{product}/edit`
- `/catalogue/masters`
- `/catalogue/masters/{type}`
- `/catalogue/masters/{type}/create`
- `/catalogue/masters/{type}/{record}`
- `/catalogue/masters/{type}/{record}/edit`

The legacy `/catalogue` route remains available, but the main sidebar entry uses `/products`.

The route requires first-run setup, login, and the `catalogue.view` permission. Creating, editing, deleting, or restoring products requires `catalogue.manage`.

## Tables

- `manufacturers`
- `categories`
- `tax_rates`
- `products`
- `product_units`
- `product_barcodes`
- `unit_masters`
- `product_type_masters`
- `dosage_form_masters`
- `schedule_label_masters`

## Product Fields

Products can store:

- Name, SKU, generic name, and composition
- Product type, form, strength, and pack size
- Manufacturer and category
- HSN code and configurable tax rate
- Schedule label entered by the store
- Prescription-required flag
- Controlled-medicine flag

## Units and Barcodes

Each product created through the Phase 5 screen receives a base unit in `product_units`. Unit conversion uses a decimal column. Optional barcode records are stored separately in `product_barcodes`.

The product form supports barcode entry in three ways:

- Manual typing
- USB barcode scanners that work as keyboard input
- Camera scanning through the local browser/Electron barcode API or the bundled ZXing decoder fallback

No online barcode lookup or internet-hosted scanner script is used in Phase 5.

## Product Workspace

The product workspace is list-first:

- Fixed sidebar navigation
- Product summary counters
- Active, all, and deleted filters
- Search by name, generic name, composition, SKU, HSN, manufacturer, or barcode
- Top-right Add New Product action
- Full-page add/edit workflow
- Top-right Product Options shortcut
- Row-level view, edit, delete, and restore actions

Delete is implemented as a safe deactivate action using `products.is_active = false`, not hard deletion.

## Product Create and Edit Pages

Product add/edit now opens as a full page, not a side drawer. The page groups product data into:

- Identity
- Category, manufacturer, and tax masters
- Medicine metadata
- Regulatory flags
- Unit and barcode details with scan controls

The product form can use saved manufacturer, category, tax-rate, unit, product-type, dosage-form, and schedule-label records. It can also create new manufacturer/category/tax records inline when no saved value exists.

## Product Options

`/catalogue/masters` is now a Product Options hub. Each option opens its own detail page with list, search, read-only view, add, edit, delete/deactivate, and restore:

- Manufacturers
- Categories with optional parent category
- Tax rates
- Units
- Product types
- Dosage forms
- Schedule labels

Master records are deactivated instead of hard deleted. Supported option types are `manufacturers`, `categories`, `tax-rates`, `units`, `product-types`, `dosage-forms`, and `schedule-labels`.

## Additional Product Details To Consider Later

These are useful, but should be added in later focused passes:

- Multiple alternate units per product
- Multiple barcodes per product and pack level
- Product images
- Product alternatives/substitutes
- Default reorder level and reorder quantity
- Storage conditions
- Expiry warning profile
- Product notes and internal handling warnings
- Supplier-specific product codes
- MRP and selling-price rules when purchase/POS phases begin

## Boundary

The catalogue does not store stock quantity, product batches, purchase prices, selling prices, suppliers, invoices, sales, returns, or inventory movements. Stock remains a future immutable movement ledger.
