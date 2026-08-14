<?php

namespace App\Livewire;

use App\Models\PurchaseOrder;
use App\Support\PurchaseOrderManager;
use Livewire\Component;

class PurchaseOrderIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'open';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.view'), 403);
    }

    public function markSent(int $orderId): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        app(PurchaseOrderManager::class)->markSent(PurchaseOrder::query()->findOrFail($orderId), auth()->user());
        session()->flash('status', 'Purchase order marked as sent.');
    }

    public function cancelOrder(int $orderId): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        app(PurchaseOrderManager::class)->cancelOrder(PurchaseOrder::query()->findOrFail($orderId), auth()->user());
        session()->flash('status', 'Purchase order cancelled.');
    }

    public function reopenOrder(int $orderId): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.manage'), 403);

        app(PurchaseOrderManager::class)->reopenOrder(PurchaseOrder::query()->findOrFail($orderId), auth()->user());
        session()->flash('status', 'Purchase order reopened.');
    }

    public function render()
    {
        return view('livewire.purchase-order-index', [
            'orders' => $this->ordersQuery()
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => PurchaseOrder::query()->count(),
                'draft' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_DRAFT)->count(),
                'sent' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_SENT)->count(),
                'cancelled' => PurchaseOrder::query()->where('status', PurchaseOrder::STATUS_CANCELLED)->count(),
                'open_value' => PurchaseOrder::query()
                    ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT])
                    ->sum('total_amount'),
            ],
            'canManage' => auth()->user()?->hasPermission('purchases.manage') ?? false,
        ]);
    }

    private function ordersQuery()
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->withCount('items')
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT]))
            ->when($this->statusFilter === PurchaseOrder::STATUS_DRAFT, fn ($query) => $query->where('status', PurchaseOrder::STATUS_DRAFT))
            ->when($this->statusFilter === PurchaseOrder::STATUS_SENT, fn ($query) => $query->where('status', PurchaseOrder::STATUS_SENT))
            ->when($this->statusFilter === PurchaseOrder::STATUS_CANCELLED, fn ($query) => $query->where('status', PurchaseOrder::STATUS_CANCELLED))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('order_number', 'like', $search)
                        ->orWhere('reference_number', 'like', $search)
                        ->orWhere('supplier_name_snapshot', 'like', $search)
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $search));
                });
            });
    }
}
