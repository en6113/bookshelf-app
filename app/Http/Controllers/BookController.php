<?php

namespace App\Http\Controllers;

use App\Enums\BookSort;
use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧表示
     */
    public function index(IndexBookRequest $request): View
    {
        $genres = Genre::all();
        $query = Book::with('genres')->withAvg('reviews', 'rating');

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orwhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンル検索
        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        // 並び順検索
        $sortEnum = BookSort::tryFrom($request->input('sort')) ?? BookSort::LATEST;
        $query = $sortEnum->apply($query);

        $books = $query->paginate(10)->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍詳細表示
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->withCount('likedByUsers');
            },
        ]);

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
     * ISBN検索
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        // 念のための簡易バリデーション
        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 422);
        }

        $key = config('services.google.books_api_key');

        // Google Books APIへリクエスト送信
        $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:'.$isbn.'&key='.$key;
        $response = Http::get($url);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['items']) && count($data['items']) > 0) {
                $volumeInfo = $data['items'][0]['volumeInfo'];

                $book = [
                    'title' => $volumeInfo['title'] ?? 'タイトル不明',
                    'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '著者不明',
                    'published_date' => $volumeInfo['publishedDate'] ?? null,
                    'description' => $volumeInfo['description'] ?? null,
                    'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
                ];

                return response()->json($book);
            }
        }

        return response()->json(['error' => '該当する書籍が見つかりませんでした。'], 404);
    }

    /**
     * 書籍保存
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $request) {
            $book = Book::create($validated);
            $book->genres()->attach($request->genres);
        });

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

        DB::transaction(function () use ($book, $validated, $request) {
            $book->update($validated);
            $book->genres()->sync($request->genres ?? []);
        });

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
