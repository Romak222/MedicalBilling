<?php

namespace App\Livewire;

use App\Models\ProductBatch;
use App\Models\StockMovement;
use Livewire\Component;

class InventoryBatchIndex extends Component
{
    public string $search = '';

    public string $expiryFilter = 'available';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('inventory.view'), 403);
    }

    public function render()
    {
        return view('livewire.inventory-batch-index', [
            'batches' => $this->batchQuery()
                ->orderBy('expires_on')
                ->limit(100)
                ->get(),
            'stats' => [
                'batches' => ProductBatch::query()->count(),
                'available' => ProductBatch::query()->where('available_quantity', '>', 0)->count(),
                'expiring' => ProductBatch::query()
                    ->where('available_quantity', '>', 0)
                    ->whereBetween('expires_on', [today(), today()->addDays(90)])
                    ->count(),
                'expired' => ProductBatch::query()->where('expires_on', '<=', today())->count(),
                'movements' => StockMovement::query()->count(),
            ],
        ]);
    }

    private function batchQuery()
    {
        return ProductBatch::query()
            ->with('product')
            ->when($this->expiryFilter === 'available', fn ($query) => $query->where('available_quantity', '>', 0))
            ->when($this->expiryFilter === 'expiring', fn ($query) => $query->where('available_quantity', '>', 0)->whereBetween('expires_on', [today(), today()->addDays(90)]))
            ->when($this->expiryFilter === 'expired', fn ($query) => $query->where('expires_on', '<=', today()))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('batch_number', 'like', $search)
                        ->orWhereHas('product', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('generic_name', 'like', $search)
                                ->orWhere('sku', 'like', $search);
                        });
                });
            });
    }
}
