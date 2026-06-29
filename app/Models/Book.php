<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * この本が属するユーザー(多対1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この本に関連するジャンル（多対多）
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre');
    }

    /**
     * この本に紐づくレビュー（1対多）
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * この本をお気に入り登録しているユーザー（多対多）
     */
    public function favoriteByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * ログインユーザーがお気に入りを切り替えるトグル処理
     */
    public function toggleFavorite(): string
    {
        $user = auth()->user();

        $result = $this->favoriteByUsers()->toggle($user->id);

        if (count($result['attached']) > 0) {
            return 'お気に入りに追加しました';
        }

        return 'お気に入りを解除しました';
    }
}
