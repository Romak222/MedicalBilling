<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Support\CustomerDirectory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomerForm extends Component
{
    public ?int $customerId = null;

    public array $customer = [
        'name' => '',
        'code' => '',
        'phone' => '',
        'email' => '',
        'gstin' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'opening_balance' => '0.00',
        'credit_limit' => '',
        'outstanding_balance' => '0.00',
        'loyalty_points' => '0',
        'reminder_consent' => false,
        'whatsapp_consent' => false,
        'sms_consent' => false,
        'notes' => '',
    ];

    public function mount(?Customer $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

        if ($record?->exists) {
            $this->fillFromCustomer($record);
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('customers.manage'), 403);

        $this->customer['reminder_consent'] = (bool) ($this->customer['reminder_consent'] ?? false);
        $this->customer['whatsapp_consent'] = (bool) ($this->customer['whatsapp_consent'] ?? false);
        $this->customer['sms_consent'] = (bool) ($this->customer['sms_consent'] ?? false);
        $validated = $this->validate();
        $directory = app(CustomerDirectory::class);

        if ($this->customerId) {
            $customer = $directory->updateCustomer(Customer::query()->findOrFail($this->customerId), $validated, auth()->user());
            session()->flash('status', 'Customer updated.');
        } else {
            $customer = $directory->createCustomer($validated, auth()->user());
            session()->flash('status', 'Customer added.');
        }

        return $this->redirectRoute('customers.show', $customer, navigate: false);
    }

    public function render()
    {
        return view('livewire.customer-form');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'customer.name' => ['required', 'string', 'max:180'],
            'customer.code' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('customers', 'code')->ignore($this->customerId)],
            'customer.phone' => ['nullable', 'string', 'max:40'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.gstin' => ['nullable', 'string', 'max:30'],
            'customer.address_line_1' => ['nullable', 'string', 'max:200'],
            'customer.address_line_2' => ['nullable', 'string', 'max:200'],
            'customer.city' => ['nullable', 'string', 'max:120'],
            'customer.state' => ['nullable', 'string', 'max:120'],
            'customer.postal_code' => ['nullable', 'string', 'max:20'],
            'customer.opening_balance' => ['nullable', 'regex:/^-?\d{1,12}(\.\d{1,2})?$/'],
            'customer.credit_limit' => ['nullable', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'customer.outstanding_balance' => ['nullable', 'regex:/^-?\d{1,12}(\.\d{1,2})?$/'],
            'customer.loyalty_points' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'customer.reminder_consent' => ['boolean'],
            'customer.whatsapp_consent' => ['boolean'],
            'customer.sms_consent' => ['boolean'],
            'customer.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function fillFromCustomer(Customer $customer): void
    {
        $this->customerId = $customer->id;
        $this->customer = [
            'name' => $customer->name,
            'code' => $customer->code ?? '',
            'phone' => $customer->phone ?? '',
            'email' => $customer->email ?? '',
            'gstin' => $customer->gstin ?? '',
            'address_line_1' => $customer->address_line_1 ?? '',
            'address_line_2' => $customer->address_line_2 ?? '',
            'city' => $customer->city ?? '',
            'state' => $customer->state ?? '',
            'postal_code' => $customer->postal_code ?? '',
            'opening_balance' => $customer->opening_balance,
            'credit_limit' => $customer->credit_limit ?? '',
            'outstanding_balance' => $customer->outstanding_balance,
            'loyalty_points' => (string) $customer->loyalty_points,
            'reminder_consent' => (bool) $customer->reminder_consent,
            'whatsapp_consent' => (bool) $customer->whatsapp_consent,
            'sms_consent' => (bool) $customer->sms_consent,
            'notes' => $customer->notes ?? '',
        ];
    }
}
