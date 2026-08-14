<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Support\CustomerDirectory;
use Livewire\Component;

class CustomerIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'active';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('customers.view'), 403);
    }

    public function deactivateCustomer(int $customerId): void
    {
        abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

        app(CustomerDirectory::class)->deactivateCustomer(Customer::query()->findOrFail($customerId), auth()->user());
        session()->flash('status', 'Customer deleted from active list.');
    }

    public function restoreCustomer(int $customerId): void
    {
        abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

        app(CustomerDirectory::class)->restoreCustomer(Customer::query()->findOrFail($customerId), auth()->user());
        session()->flash('status', 'Customer restored.');
    }

    public function render()
    {
        return view('livewire.customer-index', [
            'customers' => $this->customersQuery()
                ->withCount(['patients', 'salesInvoices'])
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => Customer::query()->count(),
                'active' => Customer::query()->where('is_active', true)->count(),
                'inactive' => Customer::query()->where('is_active', false)->count(),
                'with_balance' => Customer::query()->where('outstanding_balance', '!=', 0)->count(),
                'with_patients' => Customer::query()->has('patients')->count(),
            ],
            'canManage' => auth()->user()?->hasPermission('customers.manage') ?? false,
        ]);
    }

    private function customersQuery()
    {
        return Customer::query()
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('gstin', 'like', $search)
                        ->orWhere('city', 'like', $search)
                        ->orWhere('state', 'like', $search);
                });
            });
    }
}
