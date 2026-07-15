<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookRankingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/books');

// ユーザーのみアクセス可能なページ
Route::middleware('auth')->group(function () {
    // 書籍関係
    Route::resource('/books', BookController::class)->except('index', 'show');
    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchByIsbn']);

    // レビュー関係
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::resource('/reviews', ReviewController::class)->only('edit', 'update', 'destroy');
    Route::post('/review/{review}/like', [ReviewController::class, 'toggle'])->name('reviews.like');

    // お気に入り関係
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // ジャンル関係
    Route::resource('/genres', GenreController::class);

    // マイレポート関係
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // 読書計画関係(completeはビューに合わせてpostにしている)
    Route::post('/reading-plans/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');
    Route::resource('/reading-plans', ReadingPlanController::class);

    // 通知関係
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

// ログイン不要で閲覧できるページ（書籍一覧/書籍詳細/ランキング一覧）
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/ranking', [BookRankingController::class, 'index'])->name('ranking.index');
