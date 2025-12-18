<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    protected $table = 'idempotency_keys';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'response',
        'status_code',
        'expires_at',
    ];

    protected $casts = [
        'response' => 'array',
        'expires_at' => 'datetime',
    ];
}