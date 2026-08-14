<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_DISPENSED = 'dispensed';

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'doctor_id',
        'patient_name_snapshot',
        'patient_phone_snapshot',
        'doctor_name_snapshot',
        'prescription_date',
        'valid_until',
        'status',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'notes',
        'pharmacist_notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'prescription_date' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }

    public function isEditable(): bool
    {
        return ! $this->items->contains(fn (PrescriptionItem $item): bool => (float) $item->quantity_dispensed > 0);
    }
}
