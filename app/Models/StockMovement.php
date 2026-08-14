<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_PURCHASE_RECEIVE = 'purchase_receive';

    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_CANCEL = 'sale_cancel';

    public const TYPE_SALE_RETURN_RESTOCK = 'sale_return_restock';

    public const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';

    protected $fillable = [
        'product_id',
        'product_batch_id',
        'movement_type',
        'source_type',
        'source_id',
        'quantity',
        'unit_cost',
        'notes',
        'created_by',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }
}
