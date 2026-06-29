<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

class GetTopRatedBooksAction
{
    /**
     * 平均評価が高い本を取得するアクション
     */
    public function execute(int $limit = 10): Collection
    {
        return Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->limit($limit)
            ->get();
    }
}
