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
        'invoice_number',
        'total',
        'tax',
        'grand_total',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_uuid', 'sale_uuid');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'sale_uuid', 'sale_uuid');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_uuid', 'customer_uuid');
    }
}
