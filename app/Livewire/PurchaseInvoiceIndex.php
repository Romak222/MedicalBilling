<?php

namespace App\Livewire;

use App\Models\PurchaseInvoice;
use App\Support\PurchaseReceivingManager;
use Livewire\Component;

class PurchaseInvoiceIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'open';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.view'), 403);
    }

    public function finalizeInvoice(int $invoiceId): void
    {
        abort_unless(auth()->user()?->hasPermission('inventory.manage'), 403);

        app(PurchaseReceivingManager::class)->finalizeInvoice(PurchaseInvoice::query()->findOrFail($invoiceId), auth()->user());
        session()->flash('status', 'Purchase invoice finalized and stock received.');
    }

    public function cancelDraft(int $invoiceId): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        app(PurchaseReceivingManager::class)->cancelDraft(PurchaseInvoice::query()->findOrFail($invoiceId), auth()->user());
        session()->flash('status', 'Draft purchase invoice cancelled.');
    }

    public function render()
    {
        return view('livewire.purchase-invoice-index', [
            'invoices' => $this->invoicesQuery()
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => PurchaseInvoice::query()->count(),
                'draft' => PurchaseInvoice::query()->where('status', PurchaseInvoice::STATUS_DRAFT)->count(),
                'finalized' => PurchaseInvoice::query()->where('status', PurchaseInvoice::STATUS_FINALIZED)->count(),
                'cancelled' => PurchaseInvoice::query()->where('status', PurchaseInvoice::STATUS_CANCELLED)->count(),
                'received_value' => PurchaseInvoice::query()->where('status', PurchaseInvoice::STATUS_FINALIZED)->sum('total_amount'),
            ],
            'canManagePurchases' => auth()->user()?->hasPermission('purchases.manage') ?? false,
            'canManageInventory' => auth()->user()?->hasPermission('inventory.manage') ?? false,
        ]);
    }

    private function invoicesQuery()
    {
        return PurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder'])
            ->withCount('items')
            ->when($this->statusFilter === 'open', fn ($query) => $query->where('status', PurchaseInvoice::STATUS_DRAFT))
            ->when($this->statusFilter === PurchaseInvoice::STATUS_DRAFT, fn ($query) => $query->where('status', PurchaseInvoice::STATUS_DRAFT))
            ->when($this->statusFilter === PurchaseInvoice::STATUS_FINALIZED, fn ($query) => $query->where('status', PurchaseInvoice::STATUS_FINALIZED))
            ->when($this->statusFilter === PurchaseInvoice::STATUS_CANCELLED, fn ($query) => $query->where('status', PurchaseInvoice::STATUS_CANCELLED))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('invoice_number', 'like', $search)
                        ->orWhere('supplier_name_snapshot', 'like', $search)
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('purchaseOrder', fn ($query) => $query->where('order_number', 'like', $search));
                });
            });
    }
}
