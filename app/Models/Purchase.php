<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Purchase extends Model
{
    use HasUuid;

    protected $primaryKey = 'purchase_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'purchase_uuid',
        'tenant_uuid',
        'total',
        'supplier_uuid',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_uuid', 'purchase_uuid');
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Suppliers::class, 'supplier_uuid', 'supplier_uuid');
    }
}
