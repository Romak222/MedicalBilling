<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledMedicineRegisterEntry extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_CANCEL = 'sale_cancel';

    public const TYPE_SALE_RETURN = 'sale_return';

    protected $fillable = [
        'product_id',
        'product_batch_id',
        'customer_id',
        'patient_id',
        'doctor_id',
        'prescription_id',
        'prescription_item_id',
        'sales_invoice_id',
        'sales_invoice_item_id',
        'sales_return_id',
        'sales_return_item_id',
        'entry_type',
        'event_date',
        'quantity_effect',
        'product_name_snapshot',
        'batch_number_snapshot',
        'patient_name_snapshot',
        'doctor_name_snapshot',
        'prescription_number_snapshot',
        'invoice_number_snapshot',
        'return_number_snapshot',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'quantity_effect' => 'decimal:6',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesReturnItem(): BelongsTo
    {
        return $this->belongsTo(SalesReturnItem::class);
    }

    public function entryTypeLabel(): string
    {
        return match ($this->entry_type) {
            self::TYPE_SALE => 'Bill Dispense',
            self::TYPE_SALE_CANCEL => 'Bill Cancel Reversal',
            self::TYPE_SALE_RETURN => 'Sales Return Reversal',
            default => ucfirst(str_replace('_', ' ', $this->entry_type)),
        };
    }

    public function isPositiveEffect(): bool
    {
        return ! str_starts_with((string) $this->quantity_effect, '-');
    }
}
