<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'sales_invoice_id',
        'return_number',
        'return_date',
        'status',
        'refund_method',
        'cash_drawer_shift_id',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'refund_amount',
        'notes',
        'finalized_at',
        'finalized_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function cashDrawerShift(): BelongsTo
    {
        return $this->belongsTo(CashDrawerShift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }
}
