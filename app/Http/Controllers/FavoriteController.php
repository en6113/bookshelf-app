<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧画面表示
     */
    public function index(): View
    {
        $books = auth()->user()->favoriteBooks()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入りのトグル（登録・解除）処理
     */
    public function toggle(Book $book): RedirectResponse
    {
        $message = $book->toggleFavorite();

        return redirect()->route('books.show', $book->id)->with('success', $message);
    }
}
