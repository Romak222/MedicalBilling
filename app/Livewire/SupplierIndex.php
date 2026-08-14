<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Support\SupplierDirectory;
use Livewire\Component;

class SupplierIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('suppliers.view'), 403);
    }

    public function deactivateSupplier(int $supplierId): void
    {
        abort_unless(auth()->user()?->hasPermission('suppliers.manage'), 403);

        app(SupplierDirectory::class)->deactivateSupplier(Supplier::query()->findOrFail($supplierId), auth()->user());
        session()->flash('status', 'Supplier deleted from active list.');
    }

    public function restoreSupplier(int $supplierId): void
    {
        abort_unless(auth()->user()?->hasPermission('suppliers.manage'), 403);

        app(SupplierDirectory::class)->restoreSupplier(Supplier::query()->findOrFail($supplierId), auth()->user());
        session()->flash('status', 'Supplier restored.');
    }

    public function render()
    {
        return view('livewire.supplier-index', [
            'suppliers' => $this->suppliersQuery()
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Supplier::query()->count(),
                'active' => Supplier::query()->where('is_active', true)->count(),
                'inactive' => Supplier::query()->where('is_active', false)->count(),
                'with_balance' => Supplier::query()->where('outstanding_balance', '!=', 0)->count(),
                'over_limit' => Supplier::query()
                    ->whereNotNull('credit_limit')
                    ->whereColumn('outstanding_balance', '>', 'credit_limit')
                    ->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('suppliers.manage') ?? false,
        ]);
    }

    private function suppliersQuery()
    {
        return Supplier::query()
            ->with('primaryContact')
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('gstin', 'like', $search)
                        ->orWhere('drug_license_number', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('state', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhereHas('primaryContact', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('phone', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            });
    }
}
