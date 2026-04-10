<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    protected $fillable = [
        'tenant_uuid',
        'product_uuid',
        'quantity',
        'type',
        'reference_uuid',
        'note',
    ];
}