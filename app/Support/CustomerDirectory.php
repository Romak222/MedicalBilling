<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerDirectory
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCustomer(array $payload, User $actor): Customer
    {
        return DB::transaction(function () use ($payload, $actor): Customer {
            $attributes = $this->customerAttributes($payload, $actor);
            $attributes['created_by'] = $actor->id;
            $attributes['is_active'] = true;

            $customer = Customer::query()->create($attributes);

            app(AuditLogger::class)->record('customer.created', $actor, $customer, $this->auditMetadata($customer));

            return $customer->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateCustomer(Customer $customer, array $payload, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $payload, $actor): Customer {
            $customer->update($this->customerAttributes($payload, $actor));

            app(AuditLogger::class)->record('customer.updated', $actor, $customer, $this->auditMetadata($customer));

            return $customer->refresh();
        });
    }

    public function deactivateCustomer(Customer $customer, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $actor): Customer {
            $customer->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('customer.deactivated', $actor, $customer, $this->auditMetadata($customer));

            return $customer->refresh();
        });
    }

    public function restoreCustomer(Customer $customer, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $actor): Customer {
            $customer->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('customer.restored', $actor, $customer, $this->auditMetadata($customer));

            return $customer->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function customerAttributes(array $payload, User $actor): array
    {
        $openingBalance = $this->moneyOrZero(Arr::get($payload, 'customer.opening_balance'));

        return [
            'name' => $this->blankToNull(Arr::get($payload, 'customer.name')),
            'code' => $this->uppercaseOrNull(Arr::get($payload, 'customer.code')),
            'phone' => $this->blankToNull(Arr::get($payload, 'customer.phone')),
            'email' => $this->blankToNull(Arr::get($payload, 'customer.email')),
            'gstin' => $this->uppercaseOrNull(Arr::get($payload, 'customer.gstin')),
            'address_line_1' => $this->blankToNull(Arr::get($payload, 'customer.address_line_1')),
            'address_line_2' => $this->blankToNull(Arr::get($payload, 'customer.address_line_2')),
            'city' => $this->blankToNull(Arr::get($payload, 'customer.city')),
            'state' => $this->blankToNull(Arr::get($payload, 'customer.state')),
            'postal_code' => $this->blankToNull(Arr::get($payload, 'customer.postal_code')),
            'opening_balance' => $openingBalance,
            'credit_limit' => $this->moneyOrNull(Arr::get($payload, 'customer.credit_limit')),
            'outstanding_balance' => $this->moneyOrZero(Arr::get($payload, 'customer.outstanding_balance', $openingBalance)),
            'loyalty_points' => (int) (Arr::get($payload, 'customer.loyalty_points') ?: 0),
            'reminder_consent' => (bool) Arr::get($payload, 'customer.reminder_consent'),
            'whatsapp_consent' => (bool) Arr::get($payload, 'customer.whatsapp_consent'),
            'sms_consent' => (bool) Arr::get($payload, 'customer.sms_consent'),
            'notes' => $this->blankToNull(Arr::get($payload, 'customer.notes')),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(Customer $customer): array
    {
        return [
            'code' => $customer->code,
            'gstin_present' => $customer->gstin !== null,
            'credit_limit' => $customer->credit_limit,
            'outstanding_balance' => $customer->outstanding_balance,
            'loyalty_points' => $customer->loyalty_points,
        ];
    }

    private function moneyOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
    }

    private function moneyOrNull(mixed $value): ?string
    {
        $value = $this->blankToNull($value);

        return $value === null ? null : (string) $value;
    }

    private function uppercaseOrNull(mixed $value): ?string
    {
        $value = $this->blankToNull($value);

        return $value === null ? null : strtoupper($value);
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
