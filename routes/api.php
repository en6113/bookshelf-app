<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

// トークンを取る窓口（公開）
Route::post('/login', [AuthController::class, 'login']);

// 読み取り系（公開）
Route::get('/v1/books', [BookController::class, 'index']);
Route::get('/v1/books/{book}', [BookController::class, 'show']);

// 書き込み系（トークン認証が必要）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/books', [BookController::class, 'store']);
    Route::put('/v1/books/{book}', [BookController::class, 'update']);
    Route::delete('/v1/books/{book}', [BookController::class, 'destroy']);
});
