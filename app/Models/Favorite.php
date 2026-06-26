<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
    ];

    /**
     * このお気に入りに紐づく書籍
     */
    public function book(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
