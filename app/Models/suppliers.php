<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class suppliers extends Model
{
    use HasUuid;

    protected $primaryKey = 'supplier_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'supplier_uuid',
        'tenant_uuid',
        'name',
        'phone',
        'email',
        'address',
    ];
}
