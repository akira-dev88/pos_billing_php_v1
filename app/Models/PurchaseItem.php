<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_uuid',
        'product_uuid',
        'quantity',
        'cost_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'product_uuid');
    }
}
