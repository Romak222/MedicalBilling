<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeldSalesBill extends Model
{
    protected $fillable = [
        'hold_number',
        'customer_name',
        'customer_phone',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
