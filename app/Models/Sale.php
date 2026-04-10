<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Sale extends Model
{
    use HasUuid;

    protected $primaryKey = 'sale_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sale_uuid',
        'tenant_uuid',
        'total',
        'tax',
        'grand_total',
        'status',
    ];
}
