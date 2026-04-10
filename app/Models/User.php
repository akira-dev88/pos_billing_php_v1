<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\HasUuid;

class User extends Authenticatable
{
    use HasApiTokens, HasUuid;

    protected $primaryKey = 'user_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_uuid',
        'tenant_uuid',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}