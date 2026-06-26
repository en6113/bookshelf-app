<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧表示
     */
    public function index(): View
    {
        $books = Book::with('genres')->latest()->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍詳細表示
     */
    public function show(Book $book): View
    {
        $book->load('genres');

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録画面表示
     */
    public function create(): View
    {
        $genres = Genre::all();
        $bookGenreIds = [];

        return view('books.create', compact('genres', 'bookGenreIds'));
    }

    /**
     * 書籍保存
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $book = Book::create($validated);

        if ($request->has('genres')) {
            $book->genres()->attach($request->genres);
        }

        return redirect()->route('books.index')->with('success', '書籍を登録しました');
    }

    /**
     * 書籍編集画面表示
     */
    public function edit(Book $book): View
    {
        $genres = Genre::all();

        $this->authorize('update', $book);
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍更新
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);
        $validated = $request->validated();

        $book->update($validated);

        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        }

        return redirect()->route('books.index')->with('success', '書籍を更新しました');
    }

    /**
     * 書籍削除
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました');
    }
}
