<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'product_batch_id',
        'product_name_snapshot',
        'unit_name',
        'batch_number',
        'manufactured_on',
        'expires_on',
        'quantity',
        'free_quantity',
        'mrp',
        'purchase_rate',
        'sale_rate',
        'discount_amount',
        'tax_rate_percent',
        'line_subtotal',
        'line_tax',
        'line_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_on' => 'date',
            'expires_on' => 'date',
            'quantity' => 'decimal:6',
            'free_quantity' => 'decimal:6',
            'mrp' => 'decimal:2',
            'purchase_rate' => 'decimal:2',
            'sale_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'line_tax' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
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
