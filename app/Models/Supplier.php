<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'gstin',
        'drug_license_number',
        'drug_license_valid_until',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'phone',
        'email',
        'payment_terms_days',
        'opening_balance',
        'credit_limit',
        'outstanding_balance',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'drug_license_valid_until' => 'date',
            'payment_terms_days' => 'integer',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(SupplierContact::class)->where('is_primary', true);
    }
}
