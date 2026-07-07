<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * 特定ユーザーの読書レポート統計データを一括取得
     */
    public function getReadingStats(User $user): array
    {
        $userId = $user->id;

        // 評価分布
        $ratingCounts = $user->reviews()->pluck('rating')->countBy();
        $ratingDistribution = collect([1, 2, 3, 4, 5])->map(function ($rating) use ($ratingCounts) {
            return $ratingCounts->get($rating, 0);
        });

        // 高評価書籍TOP5
        $topRatedBooks = Book::whereHas('reviews', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('rating', '>=', 4);
        })
            ->limit(5)
            ->get()
            ->map(function ($book) use ($userId) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'rating' => $book->reviews()->where('user_id', $userId)->value('rating') ?? 0,
                ];
            })->toArray();

        // ジャンル別評価傾向TOP5
        $allGenreRatings = Genre::get()->map(function ($genre) use ($userId) {
            // ユーザーのジャンル別のレビューを取得
            $userReviewsInGenre = DB::table('book_genre')
                ->join('reviews', 'book_genre.book_id', '=', 'reviews.book_id')
                ->where('book_genre.genre_id', $genre->id)
                ->where('reviews.user_id', $userId);

            // ジャンル別のレビュー数と平均評価
            $count = $userReviewsInGenre->count();
            $averageRating = $userReviewsInGenre->avg('rating') ?? 0;

            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'count' => $count,
                'average_rating' => $averageRating,
            ];
        });

        $genreRatings = $allGenreRatings->sortByDesc('average_rating')->take(5)->values()->toArray();

        return [
            // 基本統計（総レビュー数、読了冊数、平均評価）
            'summary' => [
                'total_reviews' => $user->reviews()->count(),
                'books_read' => ReadingPlan::where('user_id', $userId)->Where('status', 'completed')->count(),
                'average_rating' => $user->reviews()->avg('rating') ?? 0,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];
    }
}
