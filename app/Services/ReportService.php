<?php

namespace App\Services;

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
        $topReviews = $user->reviews()
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        $topRatedBooks = $topReviews->map(function ($review) {
            $book = $review->book;

            return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'rating' => $review->rating,
            ];
        })->toArray();

        // ジャンル別評価傾向TOP5
        $genreRatings = DB::table('genres')
            ->join('book_genre', 'genres.id', '=', 'book_genre.genre_id')
            ->join('reviews', 'book_genre.book_id', '=', 'reviews.book_id')
            ->where('reviews.user_id', $userId)
            ->select([
                'genres.id',
                'genres.name',
            ])
            ->selectRaw('COUNT(reviews.id) as count')
            ->selectRaw('AVG(reviews.rating) as average_rating')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

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
