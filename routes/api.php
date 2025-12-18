<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransferController;

Route::get('/health', [HealthController::class, 'index']);

Route::get('/wallets', [WalletController::class, 'index']);
Route::post('/wallets', [WalletController::class, 'store']);
Route::get('/wallets/{wallet}', [WalletController::class, 'show']);

Route::get('/wallets/{wallet}/balance', [WalletController::class, 'balance']);
Route::get('/wallets/{wallet}/transactions', [WalletController::class, 'transactions']);

Route::post('/wallets/{wallet}/deposit', [WalletController::class, 'deposit']);
Route::post('/wallets/{wallet}/withdraw', [WalletController::class, 'withdraw']);

Route::post('/wallets/{wallet}/transfer', [TransferController::class, 'store']);