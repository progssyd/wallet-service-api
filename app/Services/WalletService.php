<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Convert decimal amount to minor units (e.g., 100.50 → 10050)
     */
    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert minor units to decimal (for response only)
     */
    private function fromMinorUnits(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Check if this operation was already processed using idempotency key
     */
    private function isIdempotentProcessed(string $key): bool
    {
        return \App\Models\IdempotencyKey::where('key', $key)->exists();
    }

    /**
     * Store idempotency response
     */
    private function storeIdempotencyResponse(string $key, int $status, array $response): void
    {
        \App\Models\IdempotencyKey::updateOrCreate(
            ['key' => $key],
            [
                'response' => $response,
                'status_code' => $status,
                'expires_at' => now()->addHours(24),
            ]
        );
    }

    /**
     * Deposit money into wallet
     */
    public function deposit(Wallet $wallet, float $amount, ?string $idempotencyKey = null)
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero', 400);
        }

        $amountMinor = $this->toMinorUnits($amount);

        // Idempotency check
        if ($idempotencyKey && $this->isIdempotentProcessed($idempotencyKey)) {
            $record = \App\Models\IdempotencyKey::where('key', $idempotencyKey)->first();
            return $record->response;
        }

        return DB::transaction(function () use ($wallet, $amountMinor, $idempotencyKey) {
            $wallet->increment('balance', $amountMinor);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'amount' => $amountMinor,
                'balance_after' => $wallet->fresh()->balance,
                'idempotency_key' => $idempotencyKey,
            ]);

            $newBalance = $this->fromMinorUnits($wallet->fresh()->balance);

            $response = ['balance' => $newBalance];

            if ($idempotencyKey) {
                $this->storeIdempotencyResponse($idempotencyKey, 200, $response);
            }

            return $response;
        });
    }

    /**
     * Withdraw money from wallet
     */
    public function withdraw(Wallet $wallet, float $amount, ?string $idempotencyKey = null)
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero', 400);
        }

        $amountMinor = $this->toMinorUnits($amount);

        if ($wallet->balance < $amountMinor) {
            throw new Exception('Insufficient balance', 400);
        }

        if ($idempotencyKey && $this->isIdempotentProcessed($idempotencyKey)) {
            $record = \App\Models\IdempotencyKey::where('key', $idempotencyKey)->first();
            return $record->response;
        }

        return DB::transaction(function () use ($wallet, $amountMinor, $idempotencyKey) {
            $wallet->decrement('balance', $amountMinor);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $amountMinor,
                'balance_after' => $wallet->fresh()->balance,
                'idempotency_key' => $idempotencyKey,
            ]);

            $newBalance = $this->fromMinorUnits($wallet->fresh()->balance);

            $response = ['balance' => $newBalance];

            if ($idempotencyKey) {
                $this->storeIdempotencyResponse($idempotencyKey, 200, $response);
            }

            return $response;
        });
    }

    /**
     * Transfer money between two wallets (same currency only)
     */
    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $idempotencyKey = null)
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero', 400);
        }

        if ($fromWallet->id === $toWallet->id) {
            throw new Exception('Cannot transfer to the same wallet', 400);
        }

        if ($fromWallet->currency !== $toWallet->currency) {
            throw new Exception('Currency mismatch: transfers only allowed between same currency wallets', 400);
        }

        $amountMinor = $this->toMinorUnits($amount);

        if ($fromWallet->balance < $amountMinor) {
            throw new Exception('Insufficient balance', 400);
        }

        if ($idempotencyKey && $this->isIdempotentProcessed($idempotencyKey)) {
            $record = \App\Models\IdempotencyKey::where('key', $idempotencyKey)->first();
            return $record->response;
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amountMinor, $idempotencyKey) {
            $fromWallet->decrement('balance', $amountMinor);
            $toWallet->increment('balance', $amountMinor);

            // Double-entry: out from source
            Transaction::create([
                'wallet_id' => $fromWallet->id,
                'type' => 'transfer_out',
                'amount' => $amountMinor,
                'balance_after' => $fromWallet->fresh()->balance,
                'related_wallet_id' => $toWallet->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Double-entry: in to destination
            Transaction::create([
                'wallet_id' => $toWallet->id,
                'type' => 'transfer_in',
                'amount' => $amountMinor,
                'balance_after' => $toWallet->fresh()->balance,
                'related_wallet_id' => $fromWallet->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $response = [
                'message' => 'Transfer successful',
                'from_balance' => $this->fromMinorUnits($fromWallet->fresh()->balance),
                'to_balance' => $this->fromMinorUnits($toWallet->fresh()->balance),
            ];

            if ($idempotencyKey) {
                $this->storeIdempotencyResponse($idempotencyKey, 200, $response);
            }

            return $response;
        });
    }
}