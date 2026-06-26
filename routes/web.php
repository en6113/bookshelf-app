<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/books', [BookController::class, 'index'])->name('books.index');

Route::middleware('auth')->group(function () {
    Route::resource('/books', BookController::class)->except('index', 'show');

    Route::get('/books/{book}/favorites', fn () => 'お気に入り登録（準備中）')->name('favorites.toggle');
    Route::get('/books/{book}/reviews', fn () => 'いいね登録（準備中）')->name('reviews.like');

    Route::post('/reviews', fn () => 'レビュー保存処理（準備中）')->name('reviews.store');

    Route::get('/favorites', fn () => 'お気に入り（準備中）')->name('favorites.index');

    Route::get('/genres', fn () => 'ジャンル一覧（準備中）')->name('genres.index');
});

Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/ranking', fn () => 'ランキング一覧（準備中）')->name('ranking.index');
