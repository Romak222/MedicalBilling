<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'code',
        'name',
        'legal_name',
        'gstin',
        'pan',
        'drug_license_number',
        'drug_license_valid_until',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'phone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'drug_license_valid_until' => 'date',
        ];
    }

    public function registeredPharmacists(): HasMany
    {
        return $this->hasMany(RegisteredPharmacist::class);
    }
}
