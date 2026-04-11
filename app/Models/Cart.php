<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;


class Cart extends Model
{
    use HasUuid;

    protected $primaryKey = 'cart_uuid';

    protected $fillable = [
        'cart_uuid',
        'tenant_uuid',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_uuid', 'cart_uuid');
    }
}
