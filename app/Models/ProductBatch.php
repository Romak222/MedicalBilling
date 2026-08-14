<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'manufactured_on',
        'expires_on',
        'mrp',
        'purchase_rate',
        'sale_rate',
        'available_quantity',
        'is_blocked',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_on' => 'date',
            'expires_on' => 'date',
            'mrp' => 'decimal:2',
            'purchase_rate' => 'decimal:2',
            'sale_rate' => 'decimal:2',
            'available_quantity' => 'decimal:6',
            'is_blocked' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }
}
