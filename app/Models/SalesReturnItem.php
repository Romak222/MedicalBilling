<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'sales_invoice_item_id',
        'product_id',
        'product_batch_id',
        'prescription_item_id',
        'product_name_snapshot',
        'batch_number_snapshot',
        'expires_on_snapshot',
        'unit_name',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate_percent',
        'line_subtotal',
        'line_tax',
        'line_total',
        'restock_to_inventory',
    ];

    protected function casts(): array
    {
        return [
            'expires_on_snapshot' => 'date',
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'line_tax' => 'decimal:2',
            'line_total' => 'decimal:2',
            'restock_to_inventory' => 'boolean',
        ];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }
}
