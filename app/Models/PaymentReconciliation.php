<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    protected $fillable = [
        'reconciliation_number',
        'payment_method',
        'period_from',
        'period_to',
        'settlement_date',
        'settlement_reference',
        'expected_amount',
        'settled_amount',
        'fee_amount',
        'status',
        'journal_entry_id',
        'notes',
        'reconciled_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'settlement_date' => 'date',
            'expected_amount' => 'decimal:2',
            'settled_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
