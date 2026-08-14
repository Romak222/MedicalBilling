<?php

namespace App\Support;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SupplierDirectory
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createSupplier(array $payload, User $actor): Supplier
    {
        return DB::transaction(function () use ($payload, $actor): Supplier {
            $attributes = $this->supplierAttributes($payload, $actor);
            $attributes['created_by'] = $actor->id;
            $attributes['is_active'] = true;

            $supplier = Supplier::query()->create($attributes);

            $this->syncPrimaryContact($supplier, Arr::get($payload, 'contact', []));

            app(AuditLogger::class)->record(
                'supplier.created',
                $actor,
                $supplier,
                $this->auditMetadata($supplier)
            );

            return $supplier->refresh()->load('primaryContact');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateSupplier(Supplier $supplier, array $payload, User $actor): Supplier
    {
        return DB::transaction(function () use ($supplier, $payload, $actor): Supplier {
            $supplier->update($this->supplierAttributes($payload, $actor));

            $this->syncPrimaryContact($supplier, Arr::get($payload, 'contact', []));

            app(AuditLogger::class)->record(
                'supplier.updated',
                $actor,
                $supplier,
                $this->auditMetadata($supplier)
            );

            return $supplier->refresh()->load('primaryContact');
        });
    }

    public function deactivateSupplier(Supplier $supplier, User $actor): Supplier
    {
        return DB::transaction(function () use ($supplier, $actor): Supplier {
            $supplier->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('supplier.deactivated', $actor, $supplier, $this->auditMetadata($supplier));

            return $supplier->refresh();
        });
    }

    public function restoreSupplier(Supplier $supplier, User $actor): Supplier
    {
        return DB::transaction(function () use ($supplier, $actor): Supplier {
            $supplier->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('supplier.restored', $actor, $supplier, $this->auditMetadata($supplier));

            return $supplier->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function supplierAttributes(array $payload, User $actor): array
    {
        $openingBalance = $this->moneyOrZero(Arr::get($payload, 'supplier.opening_balance'));

        return [
            'name' => $this->blankToNull(Arr::get($payload, 'supplier.name')),
            'code' => $this->uppercaseOrNull(Arr::get($payload, 'supplier.code')),
            'gstin' => $this->uppercaseOrNull(Arr::get($payload, 'supplier.gstin')),
            'drug_license_number' => $this->blankToNull(Arr::get($payload, 'supplier.drug_license_number')),
            'drug_license_valid_until' => $this->blankToNull(Arr::get($payload, 'supplier.drug_license_valid_until')),
            'address_line_1' => $this->blankToNull(Arr::get($payload, 'supplier.address_line_1')),
            'address_line_2' => $this->blankToNull(Arr::get($payload, 'supplier.address_line_2')),
            'city' => $this->blankToNull(Arr::get($payload, 'supplier.city')),
            'state' => $this->blankToNull(Arr::get($payload, 'supplier.state')),
            'postal_code' => $this->blankToNull(Arr::get($payload, 'supplier.postal_code')),
            'phone' => $this->blankToNull(Arr::get($payload, 'supplier.phone')),
            'email' => $this->blankToNull(Arr::get($payload, 'supplier.email')),
            'payment_terms_days' => $this->blankToNull(Arr::get($payload, 'supplier.payment_terms_days')),
            'opening_balance' => $openingBalance,
            'credit_limit' => $this->moneyOrNull(Arr::get($payload, 'supplier.credit_limit')),
            'outstanding_balance' => $this->moneyOrZero(Arr::get($payload, 'supplier.outstanding_balance', $openingBalance)),
            'notes' => $this->blankToNull(Arr::get($payload, 'supplier.notes')),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncPrimaryContact(Supplier $supplier, array $payload): void
    {
        $contactName = $this->blankToNull(Arr::get($payload, 'name'));
        $contactRole = $this->blankToNull(Arr::get($payload, 'role'));
        $contactPhone = $this->blankToNull(Arr::get($payload, 'phone'));
        $contactEmail = $this->blankToNull(Arr::get($payload, 'email'));

        $primaryContact = $supplier->primaryContact()->first();

        if (! $contactName && ! $contactRole && ! $contactPhone && ! $contactEmail) {
            $primaryContact?->delete();

            return;
        }

        $contactPayload = [
            'name' => $contactName ?: 'Primary Contact',
            'role' => $contactRole,
            'phone' => $contactPhone,
            'email' => $contactEmail,
            'is_primary' => true,
        ];

        if ($primaryContact) {
            $primaryContact->update($contactPayload);
        } else {
            $primaryContact = $supplier->contacts()->create($contactPayload);
        }

        $supplier->contacts()
            ->whereKeyNot($primaryContact->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(Supplier $supplier): array
    {
        return [
            'code' => $supplier->code,
            'gstin_present' => $supplier->gstin !== null,
            'drug_license_present' => $supplier->drug_license_number !== null,
            'payment_terms_days' => $supplier->payment_terms_days,
            'credit_limit' => $supplier->credit_limit,
            'outstanding_balance' => $supplier->outstanding_balance,
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
