<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * 特定ユーザーの読書レポート統計データを一括取得する
     *
     * @param  User  $user  対象ユーザー
     * @return array<string, mixed> 統計データ（基本統計/評価分布/高評価書籍/ジャンル別評価傾向）
     */
    public function getReadingStats(User $user): array
    {
        $reviews = $user->reviews()->with('book.genres')->get();

        return [
            // 基本統計（総レビュー数、読了冊数、平均評価）
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->unique('book_id')->count(),
                'average_rating' => round($reviews->avg('rating') ?? 0, 2),
            ],
            'rating_distribution' => $this->buildRatingDistribution($reviews),
            'top_rated_books' => $this->buildTopRatedBooks($reviews),
            'genre_ratings' => $this->buildGenreRatings($reviews),
        ];
    }

    /**
     * 評価分布(評価ごとのレビュー件数)を組み立てる
     *
     * @param  EloquentCollection<int, Review>  $reviews  ユーザーの全レビュー
     * @return Collection<int, int> インデックス0~4に対応する評価1~5の件数のコレクション
     */
    private function buildRatingDistribution(EloquentCollection $reviews): Collection
    {
        $ratingCounts = $reviews->countBy('rating');

        return collect([1, 2, 3, 4, 5])->map(fn (int $rating): int => $ratingCounts->get($rating, 0));
    }

    /**
     * 高評価書籍TOP5(評価4以上を評価の高い順に最大5件)を組み立てる
     *
     * @param  EloquentCollection<int, Review>  $reviews  ユーザーの全レビュー
     * @return array<int, array<string, mixed>> 書籍ID・タイトル・著者・評価の配列
     */
    private function buildTopRatedBooks(EloquentCollection $reviews): array
    {
        return $reviews
            ->where('rating', '>=', 4)
            ->sortBy([
                ['rating', 'desc'],
                ['created_at', 'desc'],
                ['id', 'asc'],
            ])
            ->take(5)
            ->map(fn (Review $review): array => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values()
            ->toArray();
    }

    /**
     * ジャンル別評価傾向TOP5（平均評価の高い順に最大5件）を組み立てる
     *
     * @param  EloquentCollection<int, Review>  $reviews  ユーザーの全レビュー
     * @return array<int, array<string, mixed> ジャンルID・名前・レビュー件数・平均評価をもつ配列
     */
    private function buildGenreRatings(EloquentCollection $reviews): array
    {
        // ジャンル単位に展開して、ジャンルごとにまとめたレビュー
        $reviewsByGenre = $reviews
            ->flatMap(fn (Review $review): Collection => $review->book->genres->map(
                fn (Genre $genre): array => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'rating' => $review->rating,
                ],
            ))
            ->groupBy('id');

        // ジャンルごとにまとめたレビューを集計して、上位5件を取得する
        return $reviewsByGenre
            ->map(fn (Collection $genreReview): array => [
                'id' => $genreReview->first()['id'],
                'name' => $genreReview->first()['name'],
                'count' => $genreReview->count(),
                'average_rating' => round($genreReview->avg('rating'), 2),
            ])
            ->sortBy([
                ['average_rating', 'desc'],
                ['count', 'desc'],
                ['id', 'asc'],
            ])
            ->take(5)
            ->values()
            ->toArray();
    }
}
