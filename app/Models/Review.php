<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
    ];

    /**
     * このレビューが属するユーザー(多対1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このレビューが属する書籍（多対1）
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * このレビューにいいねをしたユーザー（多対多）
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes');
    }

    /**
     * ログインユーザーがいいねを切り替えるトグル処理
     */
    public function toggleLike(): bool
    {
        $user = auth()->user();

        $result = $this->likedByUsers()->toggle($user->id);

        return count($result['attached']) > 0;
    }
}
