<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisteredPharmacist extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'registration_number',
        'council_name',
        'license_valid_until',
        'phone',
        'email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'license_valid_until' => 'date',
            'is_primary' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
