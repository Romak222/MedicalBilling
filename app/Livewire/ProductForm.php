<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\DosageFormMaster;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductTypeMaster;
use App\Models\ScheduleLabelMaster;
use App\Models\TaxRate;
use App\Models\UnitMaster;
use App\Support\ProductCatalogue;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    public ?int $productId = null;

    public array $product = [
        'name' => '',
        'sku' => '',
        'generic_name' => '',
        'composition' => '',
        'product_type' => '',
        'form' => '',
        'strength' => '',
        'pack_size' => '',
        'hsn_code' => '',
        'schedule_label' => '',
        'prescription_required' => false,
        'controlled_medicine' => false,
    ];

    public array $manufacturer = [
        'id' => '',
        'name' => '',
    ];

    public array $category = [
        'id' => '',
        'name' => '',
    ];

    public array $tax_rate = [
        'id' => '',
        'name' => '',
        'rate_percent' => '',
        'description' => '',
        'effective_from' => '',
    ];

    public array $unit = [
        'unit_name' => '',
        'unit_code' => '',
        'conversion_factor' => '1',
    ];

    public string $selectedUnitMasterId = '';

    public string $selectedProductTypeMasterId = '';

    public string $selectedDosageFormMasterId = '';

    public string $selectedScheduleLabelMasterId = '';

    public array $barcode = [
        'barcode' => '',
        'barcode_type' => '',
    ];

    public function mount(?Product $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        if ($record?->exists) {
            $this->fillFromProduct($record->load(['manufacturer', 'category', 'taxRate', 'baseUnit', 'barcodes']));
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        $this->clearManualMasterFieldsForSelectedOptions();

        $validated = $this->validate();
        $catalogue = app(ProductCatalogue::class);

        if ($this->productId) {
            $catalogue->updateProduct(Product::query()->findOrFail($this->productId), $validated, auth()->user());
            session()->flash('status', 'Product updated.');
        } else {
            $catalogue->createProduct($validated, auth()->user());
            session()->flash('status', 'Product added.');
        }

        return $this->redirectRoute('products.index', navigate: false);
    }

    public function useUnitMaster(): void
    {
        if ($this->selectedUnitMasterId === '') {
            return;
        }

        $unitMaster = UnitMaster::query()->findOrFail($this->selectedUnitMasterId);

        $this->unit['unit_name'] = $unitMaster->name;
        $this->unit['unit_code'] = $unitMaster->code;
    }

    public function useProductTypeMaster(): void
    {
        if ($this->selectedProductTypeMasterId === '') {
            return;
        }

        $productType = ProductTypeMaster::query()->findOrFail($this->selectedProductTypeMasterId);

        $this->product['product_type'] = $productType->name;
    }

    public function useDosageFormMaster(): void
    {
        if ($this->selectedDosageFormMasterId === '') {
            return;
        }

        $dosageForm = DosageFormMaster::query()->findOrFail($this->selectedDosageFormMasterId);

        $this->product['form'] = $dosageForm->name;
    }

    public function useScheduleLabelMaster(): void
    {
        if ($this->selectedScheduleLabelMasterId === '') {
            return;
        }

        $scheduleLabel = ScheduleLabelMaster::query()->findOrFail($this->selectedScheduleLabelMasterId);

        $this->product['schedule_label'] = $scheduleLabel->name;
    }

    public function applyScannedBarcode(string $barcode, ?string $barcodeType = null): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        $barcode = trim($barcode);

        if ($barcode === '') {
            return;
        }

        $this->barcode['barcode'] = $barcode;

        if ($barcodeType && trim($barcodeType) !== '') {
            $this->barcode['barcode_type'] = substr(strtoupper(str_replace('_', '-', trim($barcodeType))), 0, 40);
        }
    }

    public function render()
    {
        return view('livewire.product-form', [
            'manufacturers' => Manufacturer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate_percent']),
            'units' => UnitMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'productTypes' => ProductTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'dosageForms' => DosageFormMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'scheduleLabels' => ScheduleLabelMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'product.name' => ['required', 'string', 'max:180'],
            'product.sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($this->productId)],
            'product.generic_name' => ['nullable', 'string', 'max:180'],
            'product.composition' => ['nullable', 'string', 'max:2000'],
            'product.product_type' => ['nullable', 'string', 'max:60'],
            'product.form' => ['nullable', 'string', 'max:80'],
            'product.strength' => ['nullable', 'string', 'max:80'],
            'product.pack_size' => ['nullable', 'string', 'max:80'],
            'product.hsn_code' => ['nullable', 'string', 'max:20'],
            'product.schedule_label' => ['nullable', 'string', 'max:60'],
            'product.prescription_required' => ['boolean'],
            'product.controlled_medicine' => ['boolean'],
            'manufacturer.id' => ['nullable', 'integer', 'exists:manufacturers,id'],
            'manufacturer.name' => ['nullable', 'string', 'max:180'],
            'category.id' => ['nullable', 'integer', 'exists:categories,id'],
            'category.name' => ['nullable', 'string', 'max:160'],
            'tax_rate.id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'tax_rate.name' => ['nullable', 'required_with:tax_rate.rate_percent', 'string', 'max:120'],
            'tax_rate.rate_percent' => ['nullable', 'required_with:tax_rate.name', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'tax_rate.description' => ['nullable', 'string', 'max:500'],
            'tax_rate.effective_from' => ['nullable', 'date'],
            'unit.unit_name' => ['required', 'string', 'max:80'],
            'unit.unit_code' => ['required', 'string', 'max:30', 'alpha_dash'],
            'unit.conversion_factor' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,12}(?:\.\d{1,6})?$/'],
            'barcode.barcode' => ['nullable', 'string', 'max:80', Rule::unique('product_barcodes', 'barcode')->ignore($this->primaryBarcodeId())],
            'barcode.barcode_type' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function fillFromProduct(Product $product): void
    {
        $this->productId = $product->id;
        $this->product = [
            'name' => $product->name,
            'sku' => $product->sku ?? '',
            'generic_name' => $product->generic_name ?? '',
            'composition' => $product->composition ?? '',
            'product_type' => $product->product_type ?? '',
            'form' => $product->form ?? '',
            'strength' => $product->strength ?? '',
            'pack_size' => $product->pack_size ?? '',
            'hsn_code' => $product->hsn_code ?? '',
            'schedule_label' => $product->schedule_label ?? '',
            'prescription_required' => $product->prescription_required,
            'controlled_medicine' => $product->controlled_medicine,
        ];
        $this->manufacturer = [
            'id' => $product->manufacturer_id ? (string) $product->manufacturer_id : '',
            'name' => '',
        ];
        $this->category = [
            'id' => $product->category_id ? (string) $product->category_id : '',
            'name' => '',
        ];
        $this->tax_rate = [
            'id' => $product->tax_rate_id ? (string) $product->tax_rate_id : '',
            'name' => '',
            'rate_percent' => '',
            'description' => '',
            'effective_from' => '',
        ];
        $this->unit = [
            'unit_name' => $product->baseUnit?->unit_name ?? '',
            'unit_code' => $product->baseUnit?->unit_code ?? '',
            'conversion_factor' => $product->baseUnit?->conversion_factor ?? '1',
        ];
        $this->selectedUnitMasterId = '';
        $this->selectedProductTypeMasterId = '';
        $this->selectedDosageFormMasterId = '';
        $this->selectedScheduleLabelMasterId = '';
        $primaryBarcode = $product->barcodes->firstWhere('is_primary', true) ?? $product->barcodes->first();
        $this->barcode = [
            'barcode' => $primaryBarcode?->barcode ?? '',
            'barcode_type' => $primaryBarcode?->barcode_type ?? '',
        ];
    }

    private function primaryBarcodeId(): ?int
    {
        if (! $this->productId) {
            return null;
        }

        return Product::query()
            ->find($this->productId)
            ?->barcodes()
            ->where('is_primary', true)
            ->value('id');
    }

    private function clearManualMasterFieldsForSelectedOptions(): void
    {
        if ($this->manufacturer['id'] !== '') {
            $this->manufacturer['name'] = '';
        }

        if ($this->category['id'] !== '') {
            $this->category['name'] = '';
        }

        if ($this->tax_rate['id'] !== '') {
            $this->tax_rate['name'] = '';
            $this->tax_rate['rate_percent'] = '';
            $this->tax_rate['description'] = '';
            $this->tax_rate['effective_from'] = '';
        }
    }
}
