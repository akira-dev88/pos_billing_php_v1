<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_uuid',
        'product_uuid',
        'quantity',
        'price',
        'tax_percent',
        'discount'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_uuid', 'product_uuid');
    }
}
