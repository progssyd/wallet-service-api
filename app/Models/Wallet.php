<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'currency',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get balance in decimal format (e.g., 10050 → 100.50)
     */
    public function getBalanceAttribute($value)
    {
        return $value / 100;
    }

    /**
     * Set balance from decimal to minor units (e.g., 100.50 → 10050)
     */
    public function setBalanceAttribute($value)
    {
        $this->attributes['balance'] = (int) round($value * 100);
    }
}