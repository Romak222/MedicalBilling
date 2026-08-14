<?php

namespace App\Livewire;

use App\Models\SalesInvoice;
use App\Support\SalesBillingManager;
use Livewire\Component;

class SalesInvoiceIndex extends Component
{
    public string $search = '';

    public string $statusFilter = 'finalized';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.view'), 403);
    }

    public function cancelInvoice(int $invoiceId): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        app(SalesBillingManager::class)->cancelInvoice(SalesInvoice::query()->findOrFail($invoiceId), auth()->user());
        session()->flash('status', 'Sales invoice cancelled and stock reversed.');
    }

    public function render()
    {
        return view('livewire.sales-invoice-index', [
            'invoices' => $this->invoicesQuery()
                ->withCount(['items', 'salesReturns'])
                ->latest()
                ->limit(75)
                ->get(),
            'stats' => [
                'total' => SalesInvoice::query()->count(),
                'finalized' => SalesInvoice::query()->where('status', SalesInvoice::STATUS_FINALIZED)->count(),
                'cancelled' => SalesInvoice::query()->where('status', SalesInvoice::STATUS_CANCELLED)->count(),
                'sales_value' => SalesInvoice::query()->where('status', SalesInvoice::STATUS_FINALIZED)->sum('total_amount'),
            ],
            'canManage' => auth()->user()?->hasPermission('sales.manage') ?? false,
        ]);
    }

    private function invoicesQuery()
    {
        return SalesInvoice::query()
            ->with(['customer', 'patient', 'doctor', 'prescription'])
            ->when($this->statusFilter === SalesInvoice::STATUS_FINALIZED, fn ($query) => $query->where('status', SalesInvoice::STATUS_FINALIZED))
            ->when($this->statusFilter === SalesInvoice::STATUS_CANCELLED, fn ($query) => $query->where('status', SalesInvoice::STATUS_CANCELLED))
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('invoice_number', 'like', $search)
                        ->orWhere('prescription_number', 'like', $search)
                        ->orWhere('doctor_name', 'like', $search)
                        ->orWhere('customer_name', 'like', $search)
                        ->orWhere('customer_phone', 'like', $search)
                        ->orWhere('patient_name', 'like', $search)
                        ->orWhere('patient_phone', 'like', $search)
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('code', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        })
                        ->orWhereHas('patient', function ($query) use ($search): void {
                            $query
                                ->where('full_name', 'like', $search)
                                ->orWhere('patient_code', 'like', $search)
                                ->orWhere('phone', 'like', $search);
                        })
                        ->orWhereHas('doctor', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('registration_number', 'like', $search)
                                ->orWhere('clinic_name', 'like', $search);
                        });
                });
            });
    }
}
