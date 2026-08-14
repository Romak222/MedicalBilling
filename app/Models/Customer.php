<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phone',
        'email',
        'gstin',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'opening_balance',
        'credit_limit',
        'outstanding_balance',
        'loyalty_points',
        'reminder_consent',
        'whatsapp_consent',
        'sms_consent',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'loyalty_points' => 'integer',
            'reminder_consent' => 'boolean',
            'whatsapp_consent' => 'boolean',
            'sms_consent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }
}
