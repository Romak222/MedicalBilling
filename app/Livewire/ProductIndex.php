<?php

namespace App\Livewire;

use App\Models\Product;
use App\Support\ProductCatalogue;
use Livewire\Component;

class ProductIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.view'), 403);
    }

    public function deactivateProduct(int $productId): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        app(ProductCatalogue::class)->deactivateProduct(Product::query()->findOrFail($productId), auth()->user());
        session()->flash('status', 'Product deleted from active list.');
    }

    public function restoreProduct(int $productId): void
    {
        abort_unless(auth()->user()?->hasPermission('catalogue.manage'), 403);

        app(ProductCatalogue::class)->restoreProduct(Product::query()->findOrFail($productId), auth()->user());
        session()->flash('status', 'Product restored.');
    }

    public function render()
    {
        return view('livewire.product-index', [
            'products' => $this->productsQuery()
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Product::query()->count(),
                'active' => Product::query()->where('is_active', true)->count(),
                'inactive' => Product::query()->where('is_active', false)->count(),
                'rx' => Product::query()->where('prescription_required', true)->count(),
                'controlled' => Product::query()->where('controlled_medicine', true)->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('catalogue.manage') ?? false,
        ]);
    }

    private function productsQuery()
    {
        return Product::query()
            ->with(['manufacturer', 'category', 'taxRate', 'baseUnit', 'barcodes'])
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('generic_name', 'like', $search)
                        ->orWhere('composition', 'like', $search)
                        ->orWhere('sku', 'like', $search)
                        ->orWhere('hsn_code', 'like', $search)
                        ->orWhereHas('manufacturer', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('barcodes', fn ($query) => $query->where('barcode', 'like', $search));
                });
            });
    }
}
