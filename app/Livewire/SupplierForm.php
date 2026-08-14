<?php

namespace App\Livewire;

use App\Models\Supplier;
use App\Support\SupplierDirectory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SupplierForm extends Component
{
    public ?int $supplierId = null;

    public array $supplier = [
        'name' => '',
        'code' => '',
        'gstin' => '',
        'drug_license_number' => '',
        'drug_license_valid_until' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'phone' => '',
        'email' => '',
        'payment_terms_days' => '',
        'opening_balance' => '0.00',
        'credit_limit' => '',
        'outstanding_balance' => '0.00',
        'notes' => '',
    ];

    public array $contact = [
        'name' => '',
        'role' => '',
        'phone' => '',
        'email' => '',
    ];

    public function mount(?Supplier $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('suppliers.manage'), 403);

        if ($record?->exists) {
            $this->fillFromSupplier($record->load('primaryContact'));
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('suppliers.manage'), 403);

        $validated = $this->validate();
        $directory = app(SupplierDirectory::class);

        if ($this->supplierId) {
            $supplier = $directory->updateSupplier(Supplier::query()->findOrFail($this->supplierId), $validated, auth()->user());
            session()->flash('status', 'Supplier updated.');
        } else {
            $supplier = $directory->createSupplier($validated, auth()->user());
            session()->flash('status', 'Supplier added.');
        }

        return $this->redirectRoute('suppliers.show', $supplier, navigate: false);
    }

    public function render()
    {
        return view('livewire.supplier-form');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'supplier.name' => ['required', 'string', 'max:180'],
            'supplier.code' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('suppliers', 'code')->ignore($this->supplierId)],
            'supplier.gstin' => ['nullable', 'string', 'max:30'],
            'supplier.drug_license_number' => ['nullable', 'string', 'max:80'],
            'supplier.drug_license_valid_until' => ['nullable', 'date'],
            'supplier.address_line_1' => ['nullable', 'string', 'max:200'],
            'supplier.address_line_2' => ['nullable', 'string', 'max:200'],
            'supplier.city' => ['nullable', 'string', 'max:120'],
            'supplier.state' => ['nullable', 'string', 'max:120'],
            'supplier.postal_code' => ['nullable', 'string', 'max:20'],
            'supplier.phone' => ['nullable', 'string', 'max:40'],
            'supplier.email' => ['nullable', 'email', 'max:255'],
            'supplier.payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'supplier.opening_balance' => ['nullable', 'regex:/^-?\d{1,12}(\.\d{1,2})?$/'],
            'supplier.credit_limit' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'supplier.outstanding_balance' => ['nullable', 'regex:/^-?\d{1,12}(\.\d{1,2})?$/'],
            'supplier.notes' => ['nullable', 'string', 'max:5000'],
            'contact.name' => ['nullable', 'required_with:contact.role,contact.phone,contact.email', 'string', 'max:160'],
            'contact.role' => ['nullable', 'string', 'max:120'],
            'contact.phone' => ['nullable', 'string', 'max:40'],
            'contact.email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function fillFromSupplier(Supplier $supplier): void
    {
        $this->supplierId = $supplier->id;
        $this->supplier = [
            'name' => $supplier->name,
            'code' => $supplier->code ?? '',
            'gstin' => $supplier->gstin ?? '',
            'drug_license_number' => $supplier->drug_license_number ?? '',
            'drug_license_valid_until' => $supplier->drug_license_valid_until?->format('Y-m-d') ?? '',
            'address_line_1' => $supplier->address_line_1 ?? '',
            'address_line_2' => $supplier->address_line_2 ?? '',
            'city' => $supplier->city ?? '',
            'state' => $supplier->state ?? '',
            'postal_code' => $supplier->postal_code ?? '',
            'phone' => $supplier->phone ?? '',
            'email' => $supplier->email ?? '',
            'payment_terms_days' => $supplier->payment_terms_days === null ? '' : (string) $supplier->payment_terms_days,
            'opening_balance' => $supplier->opening_balance,
            'credit_limit' => $supplier->credit_limit ?? '',
            'outstanding_balance' => $supplier->outstanding_balance,
            'notes' => $supplier->notes ?? '',
        ];

        $this->contact = [
            'name' => $supplier->primaryContact?->name ?? '',
            'role' => $supplier->primaryContact?->role ?? '',
            'phone' => $supplier->primaryContact?->phone ?? '',
            'email' => $supplier->primaryContact?->email ?? '',
        ];
    }
}
