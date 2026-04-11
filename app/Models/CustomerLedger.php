<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model
{
    protected $fillable = [
        'tenant_uuid',
        'customer_uuid',
        'type',
        'amount',
        'reference_uuid',
        'note',
    ];
}
