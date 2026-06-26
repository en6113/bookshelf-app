<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/books', fn() => '書籍一覧（準備中）')->name('books.index');
Route::get('/books/{book}', fn() => '書籍詳細（準備中）')->name('books.index');
Route::get('/ranking', fn() => 'ランキング一覧（準備中）')->name('rankings.index');

Route::middleware('auth')->group(function () {
    Route::get('/books/create', fn() => '書籍登録画面（準備中）')->name('books.create');
    Route::post('/books', fn() => '書籍保存処理（準備中）')->name('books.store');
    Route::get('/books/{book}/edit', fn() => '書籍編集画面（準備中）')->name('books.edit');
    Route::put('/books/{book}', fn() => '書籍更新処理（準備中）')->name('books.update');
    Route::delete('/books/{book}', fn() => '書籍削除処理（準備中）')->name('books.destroy');

    Route::get('/books/{book}/favorites', fn() => 'お気に入り登録（準備中）');
    Route::get('/books/{book}/reviews', fn() => 'いいね登録（準備中）');

    Route::get('/reviews', fn() => 'レビュー一覧（準備中）')->name('reviews.index');

    Route::get('/favorites', fn() => 'お気に入り（準備中）');

    Route::get('/genres', fn() => 'ジャンル一覧（準備中）')->name('genres.index');
});