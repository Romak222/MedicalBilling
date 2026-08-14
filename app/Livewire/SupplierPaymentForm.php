<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Support\SubledgerManager;
use App\Support\SupplierPaymentManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SupplierPaymentForm extends Component
{
    public ?int $supplierId = null;

    public string $paymentDate = '';

    public string $paymentMethod = 'bank_transfer';

    public string $amount = '';

    public string $reference = '';

    public string $notes = '';

    public function mount(Supplier $supplier): void
    {
        abort_unless(auth()->user()?->hasPermission('accounting.manage'), 403);
        $this->supplierId = $supplier->id;
        $this->paymentDate = today()->toDateString();
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('accounting.manage'), 403);

        $this->resetValidation();
        $validated = $this->validate([
            'paymentDate' => ['required', 'date_format:Y-m-d'],
            'paymentMethod' => [Rule::in(app(SupplierPaymentManager::class)->supportedMethods())],
            'amount' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $supplier = $this->supplier();
            $payment = app(SupplierPaymentManager::class)->create($supplier, [
                'payment_date' => $validated['paymentDate'],
                'payment_method' => $validated['paymentMethod'],
                'amount' => $validated['amount'],
                'reference' => $validated['reference'],
                'notes' => $validated['notes'],
            ], auth()->user());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($this->propertyForError($field), $message);
                }
            }

            return null;
        }

        session()->flash('status', 'Supplier payment posted.');

        return $this->redirectRoute('supplier-payments.show', [$supplier, $payment], navigate: false);
    }

    public function render()
    {
        $supplier = $this->supplier();
        $statement = app(SubledgerManager::class)->supplierStatement($supplier);

        return view('livewire.supplier-payment-form', [
            'supplier' => $supplier,
            'statement' => $statement,
            'methods' => app(SupplierPaymentManager::class)->supportedMethods(),
        ]);
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->findOrFail($this->supplierId);
    }

    private function propertyForError(string $field): string
    {
        return match ($field) {
            'payment_date' => 'paymentDate',
            'payment_method' => 'paymentMethod',
            default => $field,
        };
    }
}
