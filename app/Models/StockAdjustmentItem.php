<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_batch_id',
        'product_id',
        'product_name_snapshot',
        'batch_number_snapshot',
        'before_quantity',
        'counted_quantity',
        'delta_quantity',
        'unit_cost',
        'value_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'before_quantity' => 'decimal:6',
            'counted_quantity' => 'decimal:6',
            'delta_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'value_amount' => 'decimal:2',
        ];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
