<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductCatalogue
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createProduct(array $payload, User $actor): Product
    {
        return DB::transaction(function () use ($payload, $actor): Product {
            $manufacturer = $this->resolveNamedModel(Manufacturer::class, Arr::get($payload, 'manufacturer', []), $actor);
            $category = $this->resolveNamedModel(Category::class, Arr::get($payload, 'category', []), $actor);
            $taxRate = $this->resolveTaxRate(Arr::get($payload, 'tax_rate', []), $actor);

            $product = Product::query()->create([
                'name' => $this->blankToNull(Arr::get($payload, 'product.name')),
                'sku' => $this->blankToNull(Arr::get($payload, 'product.sku')),
                'generic_name' => $this->blankToNull(Arr::get($payload, 'product.generic_name')),
                'composition' => $this->blankToNull(Arr::get($payload, 'product.composition')),
                'product_type' => $this->blankToNull(Arr::get($payload, 'product.product_type')),
                'form' => $this->blankToNull(Arr::get($payload, 'product.form')),
                'strength' => $this->blankToNull(Arr::get($payload, 'product.strength')),
                'pack_size' => $this->blankToNull(Arr::get($payload, 'product.pack_size')),
                'manufacturer_id' => $manufacturer?->id,
                'category_id' => $category?->id,
                'tax_rate_id' => $taxRate?->id,
                'hsn_code' => $this->blankToNull(Arr::get($payload, 'product.hsn_code')),
                'schedule_label' => $this->blankToNull(Arr::get($payload, 'product.schedule_label')),
                'prescription_required' => (bool) Arr::get($payload, 'product.prescription_required', false),
                'controlled_medicine' => (bool) Arr::get($payload, 'product.controlled_medicine', false),
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $product->units()->create([
                'unit_name' => $this->blankToNull(Arr::get($payload, 'unit.unit_name')),
                'unit_code' => strtoupper((string) Arr::get($payload, 'unit.unit_code')),
                'conversion_factor' => (string) Arr::get($payload, 'unit.conversion_factor'),
                'is_base' => true,
                'sellable' => true,
                'purchasable' => true,
            ]);

            if ($barcode = $this->blankToNull(Arr::get($payload, 'barcode.barcode'))) {
                $product->barcodes()->create([
                    'barcode' => $barcode,
                    'barcode_type' => $this->blankToNull(Arr::get($payload, 'barcode.barcode_type')),
                    'is_primary' => true,
                ]);
            }

            app(AuditLogger::class)->record(
                'catalogue.product.created',
                $actor,
                $product,
                [
                    'prescription_required' => $product->prescription_required,
                    'controlled_medicine' => $product->controlled_medicine,
                ]
            );

            return $product->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProduct(Product $product, array $payload, User $actor): Product
    {
        return DB::transaction(function () use ($product, $payload, $actor): Product {
            $manufacturer = $this->resolveNamedModel(Manufacturer::class, Arr::get($payload, 'manufacturer', []), $actor);
            $category = $this->resolveNamedModel(Category::class, Arr::get($payload, 'category', []), $actor);
            $taxRate = $this->resolveTaxRate(Arr::get($payload, 'tax_rate', []), $actor);

            $product->update([
                'name' => $this->blankToNull(Arr::get($payload, 'product.name')),
                'sku' => $this->blankToNull(Arr::get($payload, 'product.sku')),
                'generic_name' => $this->blankToNull(Arr::get($payload, 'product.generic_name')),
                'composition' => $this->blankToNull(Arr::get($payload, 'product.composition')),
                'product_type' => $this->blankToNull(Arr::get($payload, 'product.product_type')),
                'form' => $this->blankToNull(Arr::get($payload, 'product.form')),
                'strength' => $this->blankToNull(Arr::get($payload, 'product.strength')),
                'pack_size' => $this->blankToNull(Arr::get($payload, 'product.pack_size')),
                'manufacturer_id' => $manufacturer?->id,
                'category_id' => $category?->id,
                'tax_rate_id' => $taxRate?->id,
                'hsn_code' => $this->blankToNull(Arr::get($payload, 'product.hsn_code')),
                'schedule_label' => $this->blankToNull(Arr::get($payload, 'product.schedule_label')),
                'prescription_required' => (bool) Arr::get($payload, 'product.prescription_required', false),
                'controlled_medicine' => (bool) Arr::get($payload, 'product.controlled_medicine', false),
                'updated_by' => $actor->id,
            ]);

            $baseUnit = $product->baseUnit()->first();
            $unitPayload = [
                'unit_name' => $this->blankToNull(Arr::get($payload, 'unit.unit_name')),
                'unit_code' => strtoupper((string) Arr::get($payload, 'unit.unit_code')),
                'conversion_factor' => (string) Arr::get($payload, 'unit.conversion_factor'),
                'is_base' => true,
                'sellable' => true,
                'purchasable' => true,
            ];

            $baseUnit ? $baseUnit->update($unitPayload) : $product->units()->create($unitPayload);

            $primaryBarcode = $product->barcodes()->where('is_primary', true)->first();
            $barcode = $this->blankToNull(Arr::get($payload, 'barcode.barcode'));

            if ($barcode) {
                $barcodePayload = [
                    'barcode' => $barcode,
                    'barcode_type' => $this->blankToNull(Arr::get($payload, 'barcode.barcode_type')),
                    'is_primary' => true,
                ];

                $primaryBarcode ? $primaryBarcode->update($barcodePayload) : $product->barcodes()->create($barcodePayload);
            } elseif ($primaryBarcode) {
                $primaryBarcode->delete();
            }

            app(AuditLogger::class)->record(
                'catalogue.product.updated',
                $actor,
                $product,
                [
                    'prescription_required' => $product->prescription_required,
                    'controlled_medicine' => $product->controlled_medicine,
                ]
            );

            return $product->refresh();
        });
    }

    public function deactivateProduct(Product $product, User $actor): Product
    {
        return DB::transaction(function () use ($product, $actor): Product {
            $product->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('catalogue.product.deactivated', $actor, $product);

            return $product->refresh();
        });
    }

    public function restoreProduct(Product $product, User $actor): Product
    {
        return DB::transaction(function () use ($product, $actor): Product {
            $product->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('catalogue.product.restored', $actor, $product);

            return $product->refresh();
        });
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function resolveNamedModel(string $model, array $payload, User $actor): ?Model
    {
        $id = $this->blankToNull(Arr::get($payload, 'id'));

        if ($id) {
            return $model::query()->findOrFail($id);
        }

        return $this->findOrCreateNamed($model, Arr::get($payload, 'name'), $actor);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function findOrCreateNamed(string $model, mixed $name, User $actor): ?Model
    {
        $name = $this->blankToNull($name);

        if (! $name) {
            return null;
        }

        return $model::query()->firstOrCreate(
            ['name' => $name],
            [
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveTaxRate(array $payload, User $actor): ?TaxRate
    {
        $id = $this->blankToNull(Arr::get($payload, 'id'));

        if ($id) {
            return TaxRate::query()->findOrFail($id);
        }

        return $this->findOrCreateTaxRate($payload, $actor);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findOrCreateTaxRate(array $payload, User $actor): ?TaxRate
    {
        $name = $this->blankToNull(Arr::get($payload, 'name'));
        $rate = $this->blankToNull(Arr::get($payload, 'rate_percent'));

        if (! $name && ! $rate) {
            return null;
        }

        return TaxRate::query()->firstOrCreate(
            ['name' => $name],
            [
                'rate_percent' => (string) $rate,
                'description' => $this->blankToNull(Arr::get($payload, 'description')),
                'effective_from' => $this->blankToNull(Arr::get($payload, 'effective_from')),
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]
        );
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
