<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

class GetTopRatedBooksAction
{
    /**
     * 平均評価が高い本を取得するアクション(レビューのない書籍は除外)
     *
     * @param  int  $limit  取得する最大件数
     * @return Collection<int, Book> 平均評価の降順で並んだ書籍コレクション
     */
    public function execute(int $limit = 10): Collection
    {
        return Book::query()
            ->has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->limit($limit)
            ->get();
    }
}
