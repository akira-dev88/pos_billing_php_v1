<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'tenant_uuid',
        'shop_name',
        'mobile',
        'address',
        'gstin',
        'invoice_prefix',
    ];
}
