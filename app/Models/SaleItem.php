<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_uuid',
        'product_uuid',
        'quantity',
        'price',
        'tax_percent',
        'tax_amount',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'product_uuid');
    }
}
