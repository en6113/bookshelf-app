<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'status' => ReadingPlanStatus::class,
    ];

    /**
     * この読書計画が属するユーザー(多対1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この読書計画が属する書籍（多対1）
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * 読書計画一覧で状態で絞り込むためのスコープ
     */
    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        $statusEnum = ReadingPlanStatus::tryFrom($status);

        return $statusEnum
            ? $query->where('status', $statusEnum->value)
            : $query;
    }
}
