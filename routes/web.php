<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// ユーザーのみアクセス可能なページ
Route::middleware('auth')->group(function () {
    // 書籍関係
    Route::resource('/books', BookController::class)->except('index', 'show');

    // レビュー関係
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::resource('/reviews', ReviewController::class)->only('edit', 'update', 'destroy');
    Route::get('/review/{review}/like', fn () => 'いいね登録（準備中）')->name('reviews.like');

    // お気に入り関係
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // ジャンル関係
    Route::resource('/genres', GenreController::class);
});

// ログイン不要で閲覧できるページ（書籍一覧/書籍詳細/ランキング一覧）
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/ranking', fn () => 'ランキング一覧（準備中）')->name('ranking.index');
