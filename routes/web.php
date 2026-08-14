<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\ControlledMedicineRegisterEntry;
use App\Models\CashDrawerShift;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Support\CatalogueOptionRegistry;
use App\Support\CashDrawerManager;
use App\Support\FirstRunSetup;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('system.status.view'), 403);

    return view('dashboard', [
        'setupComplete' => true,
        'primaryStore' => $setup->primaryStore(),
    ]);
})->name('dashboard');

Route::get('/status', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('system.status.view'), 403);

    return view('dashboard', [
        'setupComplete' => true,
        'primaryStore' => $setup->primaryStore(),
    ]);
})->name('status');

Route::get('/catalogue', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.view'), 403);

    return view('catalogue.index');
})->name('catalogue.index');

Route::get('/products', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.view'), 403);

    return view('catalogue.index');
})->name('products.index');

Route::get('/products/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    return view('catalogue.form');
})->name('products.create');

Route::get('/products/{product}', function (FirstRunSetup $setup, Product $product) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.view'), 403);

    return view('catalogue.product-show', [
        'product' => $product->load(['manufacturer', 'category', 'taxRate', 'baseUnit', 'barcodes']),
    ]);
})->name('products.show');

Route::get('/products/{product}/edit', function (FirstRunSetup $setup, Product $product) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    return view('catalogue.form', ['product' => $product]);
})->name('products.edit');

Route::get('/catalogue/masters', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    return view('catalogue.masters', [
        'options' => CatalogueOptionRegistry::all(),
    ]);
})->name('catalogue.masters');

Route::get('/catalogue/masters/{type}', function (FirstRunSetup $setup, string $type) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    CatalogueOptionRegistry::get($type);

    return view('catalogue.option', ['type' => $type]);
})->name('catalogue.options.show');

Route::get('/catalogue/masters/{type}/create', function (FirstRunSetup $setup, string $type) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    CatalogueOptionRegistry::get($type);

    return view('catalogue.option', ['type' => $type]);
})->name('catalogue.options.create');

Route::get('/catalogue/masters/{type}/{record}', function (FirstRunSetup $setup, string $type, int $record) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    $config = CatalogueOptionRegistry::get($type);
    $model = $config['model'];
    $query = $model::query();

    if ($with = ($config['with'] ?? [])) {
        $query->with($with);
    }

    return view('catalogue.option-show', [
        'type' => $type,
        'config' => $config,
        'record' => $query->findOrFail($record),
    ]);
})->name('catalogue.options.view');

Route::get('/catalogue/masters/{type}/{record}/edit', function (FirstRunSetup $setup, string $type, int $record) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('catalogue.manage'), 403);

    $config = CatalogueOptionRegistry::get($type);
    $model = $config['model'];

    return view('catalogue.option', [
        'type' => $type,
        'record' => $model::query()->findOrFail($record),
    ]);
})->name('catalogue.options.edit');

Route::get('/suppliers', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('suppliers.view'), 403);

    return view('suppliers.index');
})->name('suppliers.index');

Route::get('/suppliers/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('suppliers.manage'), 403);

    return view('suppliers.form');
})->name('suppliers.create');

Route::get('/suppliers/{supplier}', function (FirstRunSetup $setup, Supplier $supplier) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('suppliers.view'), 403);

    return view('suppliers.show', [
        'supplier' => $supplier->load('primaryContact'),
    ]);
})->name('suppliers.show');

Route::get('/suppliers/{supplier}/edit', function (FirstRunSetup $setup, Supplier $supplier) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('suppliers.manage'), 403);

    return view('suppliers.form', ['supplier' => $supplier]);
})->name('suppliers.edit');

Route::get('/customers', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('customers.view'), 403);

    return view('customers.index');
})->name('customers.index');

Route::get('/customers/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

    return view('customers.form');
})->name('customers.create');

Route::get('/customers/{customer}', function (FirstRunSetup $setup, Customer $customer) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('customers.view'), 403);

    return view('customers.show', [
        'customer' => $customer->load(['patients.doctor', 'salesInvoices.patient']),
    ]);
})->name('customers.show');

Route::get('/customers/{customer}/edit', function (FirstRunSetup $setup, Customer $customer) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

    return view('customers.form', ['customer' => $customer]);
})->name('customers.edit');

Route::get('/patients', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('patients.view'), 403);

    return view('patients.index');
})->name('patients.index');

Route::get('/patients/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

    return view('patients.form');
})->name('patients.create');

Route::get('/patients/{patient}', function (FirstRunSetup $setup, Patient $patient) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('patients.view'), 403);

    return view('patients.show', [
        'patient' => $patient->load(['customer', 'doctor', 'prescriptions.doctor', 'salesInvoices.customer']),
    ]);
})->name('patients.show');

Route::get('/patients/{patient}/edit', function (FirstRunSetup $setup, Patient $patient) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('patients.manage'), 403);

    return view('patients.form', ['patient' => $patient]);
})->name('patients.edit');

Route::get('/doctors', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('doctors.view'), 403);

    return view('doctors.index');
})->name('doctors.index');

Route::get('/doctors/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

    return view('doctors.form');
})->name('doctors.create');

Route::get('/doctors/{doctor}', function (FirstRunSetup $setup, Doctor $doctor) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('doctors.view'), 403);

    return view('doctors.show', [
        'doctor' => $doctor->load(['patients.customer', 'prescriptions.patient']),
    ]);
})->name('doctors.show');

Route::get('/doctors/{doctor}/edit', function (FirstRunSetup $setup, Doctor $doctor) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('doctors.manage'), 403);

    return view('doctors.form', ['doctor' => $doctor]);
})->name('doctors.edit');

Route::get('/prescriptions', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);

    return view('prescriptions.index');
})->name('prescriptions.index');

Route::get('/prescriptions/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

    return view('prescriptions.form');
})->name('prescriptions.create');

Route::get('/prescriptions/refills', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);

    return view('prescriptions.refills.index');
})->name('prescription-refills.index');

Route::get('/prescriptions/refills/{prescriptionItem}', function (FirstRunSetup $setup, PrescriptionItem $prescriptionItem) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);

    return view('prescriptions.refills.show', [
        'prescriptionItem' => $prescriptionItem->load([
            'product',
            'prescription.patient.customer',
            'prescription.doctor',
            'salesInvoiceItems.salesInvoice',
            'salesInvoiceItems.salesReturnItems',
        ]),
    ]);
})->name('prescription-refills.show');

Route::get('/prescriptions/{prescription}', function (FirstRunSetup $setup, Prescription $prescription) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);

    return view('prescriptions.show', [
        'prescription' => $prescription->load(['patient.customer', 'doctor', 'items.product', 'items.prescription', 'salesInvoices.customer', 'salesInvoices.patient', 'salesInvoices.doctor']),
    ]);
})->name('prescriptions.show');

Route::get('/prescriptions/{prescription}/attachment', function (FirstRunSetup $setup, Prescription $prescription) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.view'), 403);
    abort_if(! $prescription->attachment_path, 404);

    return response()->download(
        Storage::disk('local')->path($prescription->attachment_path),
        $prescription->attachment_original_name ?? basename($prescription->attachment_path)
    );
})->name('prescriptions.attachment');

Route::get('/prescriptions/{prescription}/edit', function (FirstRunSetup $setup, Prescription $prescription) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

    return view('prescriptions.form', ['prescription' => $prescription]);
})->name('prescriptions.edit');

Route::get('/controlled-medicines', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('controlled_medicines.view'), 403);

    return view('controlled-medicines.index');
})->name('controlled-medicines.index');

Route::get('/controlled-medicines/{entry}', function (FirstRunSetup $setup, ControlledMedicineRegisterEntry $entry) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()?->hasPermission('controlled_medicines.view'), 403);

    return view('controlled-medicines.show', [
        'entry' => $entry->load(['product', 'productBatch', 'customer', 'patient', 'doctor', 'prescription', 'salesInvoice', 'salesReturn']),
    ]);
})->name('controlled-medicines.show');

Route::get('/purchases', function () {
    return redirect()->route('purchase-orders.index');
})->name('purchases.index');

Route::get('/purchases/orders', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.view'), 403);

    return view('purchases.orders.index');
})->name('purchase-orders.index');

Route::get('/purchases/orders/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.manage'), 403);

    return view('purchases.orders.form');
})->name('purchase-orders.create');

Route::get('/purchases/orders/{purchaseOrder}', function (FirstRunSetup $setup, PurchaseOrder $purchaseOrder) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.view'), 403);

    return view('purchases.orders.show', [
        'purchaseOrder' => $purchaseOrder->load(['supplier', 'items.product']),
    ]);
})->name('purchase-orders.show');

Route::get('/purchases/orders/{purchaseOrder}/edit', function (FirstRunSetup $setup, PurchaseOrder $purchaseOrder) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.manage'), 403);

    return view('purchases.orders.form', ['purchaseOrder' => $purchaseOrder]);
})->name('purchase-orders.edit');

Route::get('/purchases/invoices', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.view'), 403);

    return view('purchases.invoices.index');
})->name('purchase-invoices.index');

Route::get('/purchases/invoices/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.manage'), 403);

    return view('purchases.invoices.form');
})->name('purchase-invoices.create');

Route::get('/purchases/invoices/{purchaseInvoice}', function (FirstRunSetup $setup, PurchaseInvoice $purchaseInvoice) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.view'), 403);

    return view('purchases.invoices.show', [
        'purchaseInvoice' => $purchaseInvoice->load(['supplier', 'purchaseOrder', 'items.product', 'items.productBatch']),
    ]);
})->name('purchase-invoices.show');

Route::get('/purchases/invoices/{purchaseInvoice}/edit', function (FirstRunSetup $setup, PurchaseInvoice $purchaseInvoice) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('purchases.manage'), 403);

    return view('purchases.invoices.form', ['purchaseInvoice' => $purchaseInvoice]);
})->name('purchase-invoices.edit');

Route::get('/inventory/batches', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('inventory.view'), 403);

    return view('inventory.batches.index');
})->name('inventory.batches.index');

Route::get('/cash-drawer', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('cash_drawer.view'), 403);

    return view('cash-drawer.index');
})->name('cash-drawer.index');

Route::get('/cash-drawer/{cashDrawerShift}', function (FirstRunSetup $setup, CashDrawerShift $cashDrawerShift, CashDrawerManager $cashDrawerManager) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('cash_drawer.view'), 403);

    return view('cash-drawer.show', [
        'cashDrawerShift' => $cashDrawerShift->load(['openedBy', 'closedBy', 'entries.createdBy', 'salesInvoices', 'salesReturns']),
        'totals' => $cashDrawerManager->calculateTotals($cashDrawerShift),
    ]);
})->name('cash-drawer.show');

Route::get('/billing', function () {
    return redirect()->route('sales-invoices.index');
})->name('billing.index');

Route::get('/billing/sales', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.view'), 403);

    return view('sales.index');
})->name('sales-invoices.index');

Route::get('/billing/sales/create', function (FirstRunSetup $setup) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.manage'), 403);

    return view('sales.form');
})->name('sales-invoices.create');

Route::get('/billing/sales/{salesInvoice}', function (FirstRunSetup $setup, SalesInvoice $salesInvoice) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.view'), 403);

    return view('sales.show', [
        'salesInvoice' => $salesInvoice->load(['customer', 'patient', 'doctor', 'prescription', 'cashDrawerShift', 'items.product', 'items.productBatch', 'items.prescriptionItem', 'salesReturns.items']),
    ]);
})->name('sales-invoices.show');

Route::get('/billing/sales/{salesInvoice}/receipt', function (FirstRunSetup $setup, SalesInvoice $salesInvoice) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.view'), 403);

    return view('sales.receipt', [
        'salesInvoice' => $salesInvoice->load(['customer', 'patient', 'doctor', 'prescription', 'items.product', 'items.productBatch']),
    ]);
})->name('sales-invoices.receipt');

Route::get('/billing/sales/{salesInvoice}/returns/create', function (FirstRunSetup $setup, SalesInvoice $salesInvoice) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.manage'), 403);

    return view('sales.return-form', [
        'salesInvoice' => $salesInvoice->load(['items.salesReturnItems', 'items.productBatch']),
    ]);
})->name('sales-returns.create');

Route::get('/billing/returns/{salesReturn}', function (FirstRunSetup $setup, SalesReturn $salesReturn) {
    if (! $setup->isComplete()) {
        return redirect()->route('setup');
    }

    if (! auth()->check()) {
        return redirect()->guest(route('login'));
    }

    abort_unless(auth()->user()->hasPermission('sales.view'), 403);

    return view('sales.return-show', [
        'salesReturn' => $salesReturn->load(['salesInvoice', 'cashDrawerShift', 'items.product', 'items.productBatch']),
    ]);
})->name('sales-returns.show');

Route::get('/setup', function (FirstRunSetup $setup) {
    if ($setup->isComplete()) {
        return redirect()->route('status');
    }

    return view('setup');
})->name('setup');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
