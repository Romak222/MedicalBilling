<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'customer_id',
        'primary_doctor_id',
        'full_name',
        'patient_code',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'primary_doctor_name',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'allergies',
        'medical_notes',
        'notes',
        'reminder_consent',
        'whatsapp_consent',
        'sms_consent',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'reminder_consent' => 'boolean',
            'whatsapp_consent' => 'boolean',
            'sms_consent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }
}
