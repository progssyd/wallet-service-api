<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoneyOperationRequest;
use App\Http\Requests\StoreWalletRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Wallet::query();

        if ($request->has('owner_name')) {
            $query->where('owner_name', 'like', '%' . $request->owner_name . '%');
        }

        if ($request->has('currency')) {
            $query->where('currency', $request->currency);
        }

        $wallets = $query->get();

        return response()->json($wallets);
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        $wallet = Wallet::create($request->validated());

        return response()->json($wallet, 201);
    }

    public function show(Wallet $wallet): JsonResponse
    {
        return response()->json($wallet);
    }

    public function balance(Wallet $wallet): JsonResponse
    {
        return response()->json(['balance' => $wallet->balance]);
    }

    public function deposit(Wallet $wallet, MoneyOperationRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->walletService->deposit($wallet, $request->amount, $idempotencyKey);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function withdraw(Wallet $wallet, MoneyOperationRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $result = $this->walletService->withdraw($wallet, $request->amount, $idempotencyKey);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function transactions(Wallet $wallet, Request $request): JsonResponse
    {
        $query = $wallet->transactions()->latest();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $transactions = $query->paginate(20);

        return response()->json($transactions);
    }
}