<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'related_wallet_id',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function relatedWallet()
    {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    /**
     * Amount in decimal for display
     */
    public function getAmountAttribute($value)
    {
        return $value / 100;
    }

    public function getBalanceAfterAttribute($value)
    {
        return $value / 100;
    }
}