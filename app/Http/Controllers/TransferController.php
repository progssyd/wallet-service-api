<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function store(TransferRequest $request, Wallet $fromWallet): JsonResponse
    {
        $toWallet = Wallet::findOrFail($request->to_wallet_id);
        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->walletService->transfer($fromWallet, $toWallet, $request->amount, $idempotencyKey);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}