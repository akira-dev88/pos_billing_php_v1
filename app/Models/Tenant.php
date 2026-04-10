<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Tenant extends Model
{
    use HasUuid;

    protected $primaryKey = 'tenant_uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
    ];
}