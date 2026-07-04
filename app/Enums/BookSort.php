<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

enum BookSort: string
{
    case LATEST = 'latest';
    case OLDEST = 'oldest';
    case TITLE_ASC = 'title';
    case RATING_DESC = 'rating';

    /**
     * Enumの値に応じたクエリのソート処理を適用する
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::LATEST => $query->latest('created_at'),
            self::OLDEST => $query->oldest('created_at'),
            self::TITLE_ASC => $query->orderBy('title', 'asc'),
            self::RATING_DESC => $query->orderByDesc('reviews_avg_rating'),
        };
    }
}
