<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Product extends Model
{
    use HasUuid;

    protected $primaryKey = 'product_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'product_uuid',
        'tenant_uuid',
        'name',
        'barcode',
        'sku',
        'price',
        'gst_percent',
        'stock',
    ];
}