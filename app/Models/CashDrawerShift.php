<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashDrawerShift extends \Illuminate\Database\Eloquent\Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'shift_number',
        'status',
        'opened_at',
        'closed_at',
        'opening_float',
        'cash_sales_amount',
        'cash_refunds_amount',
        'cash_in_amount',
        'cash_out_amount',
        'expected_closing_cash',
        'counted_closing_cash',
        'variance_amount',
        'opening_notes',
        'closing_notes',
        'opened_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'cash_sales_amount' => 'decimal:2',
            'cash_refunds_amount' => 'decimal:2',
            'cash_in_amount' => 'decimal:2',
            'cash_out_amount' => 'decimal:2',
            'expected_closing_cash' => 'decimal:2',
            'counted_closing_cash' => 'decimal:2',
            'variance_amount' => 'decimal:2',
        ];
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CashDrawerEntry::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
