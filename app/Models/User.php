<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * このユーザーが作成した書籍(1対多)
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * このユーザーが投稿したレビュー(1対多)
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * このユーザーがお気に入り登録した書籍（多対多）
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->BelongsToMany(Book::class, 'favorites');
    }

    /**
     * このユーザーがいいねしたレビュー（多対多）
     */
    public function likedReviews(): BelongsToMany
    {
        return $this->BelongsToMany(Review::class, 'review_likes');
    }

    /**
     * このユーザーが作成した読書計画(1対多)
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }
}
