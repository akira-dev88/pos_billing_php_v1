<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\HasUuid;

class Customer extends Model
{
    use HasUuid;

    protected $primaryKey = 'customer_uuid';

    protected $fillable = [
        'customer_uuid',
        'tenant_uuid',
        'name',
        'mobile',
        'address',
        'gstin',
        'credit_balance',
    ];
}
