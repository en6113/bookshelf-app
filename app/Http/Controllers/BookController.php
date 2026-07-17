<?php

namespace App\Http\Controllers;

use App\Enums\BookSort;
use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧表示(キーワード・ジャンル・並び順の検索対応)
     *
     * @param  IndexBookRequest  $request  検索条件のバリデーション済リクエスト
     * @return View 書籍一覧画面
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
     * 書籍詳細表示（ジャンル・レビュー・いいね数付き）
     *
     * @param  Book  $book  表示対象の書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews' => fn ($query) => $query->with('user')->withCount('likedByUsers'),
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録画面を表示する
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();
        $bookGenreIds = [];

        return view('books.create', compact('genres', 'bookGenreIds'));
    }

    /**
     * GoogleBooksAPIでISBN検索し、書籍情報をJSONで返す
     *
     * @param  string  $isbn  13桁のISBNコード
     * @return JsonResponse 書籍情報（title/author/published_date/description/image_url）またはエラー
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        // 念のための簡易バリデーション
        if (strlen($isbn) !== 13) {
            return response()->json(['error' => 'ISBNは13桁で入力してください。'], 422);
        }

        try {
            $response = Http::timeout(5)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:'.$isbn,
                'key' => config('services.google.books_api_key'),
            ]);
        } catch (ConnectionException) {
            return response()->json(['error' => '書籍情報の取得に失敗しました。時間をおいて再度お試しください'], 503);
        }

        $volumeInfo = $response->successful() ? $response->json('items.0.volumeInfo') : null;

        if ($volumeInfo === null) {
            return response()->json(['error' => '該当する書籍が見つかりませんでした'], 404);
        }

        return response()->json([
            'title' => $volumeInfo['title'] ?? 'タイトル不明',
            'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '著者不明',
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
        ]);
    }

    /**
     * 書籍を登録し、ジャンルを紐づける（トランザクションで原子化）
     *
     * @param  StoreBookRequest  $request  バリデーション済みリクエスト
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $genres = $request->input('genres', []);
        $validated = $request->safe()->except('genres');
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $genres) {
            $book = Book::create($validated);
            $book->genres()->attach($genres);
        });

        return redirect()->route('books.index')->with('success', '書籍を登録しました');
    }

    /**
     * 書籍編集画面を表示する（作成者のみ）
     *
     * @param  Book  $book  編集対象の書籍
     * @return View 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍を更新し、ジャンルを同期する（作成者のみ、トランザクションで原子化）
     *
     * @param  UpdateBookRequest  $request  バリデーション済みのリクエスト
     * @param  Book  $book  更新対象の書籍
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $genres = $request->input('genres', []);
        $validated = $request->safe()->except('genres');

        DB::transaction(function () use ($book, $validated, $genres) {
            $book->update($validated);
            $book->genres()->sync($genres);
        });

        return redirect()->route('books.index')->with('success', '書籍を更新しました');
    }

    /**
     * 書籍を削除する（作成者のみ、関連レコードはCascadeで削除）
     *
     * @param  Book  $book  削除対象の書籍
     * @return RedirectResponse 書籍一覧画面へのリダイレクト
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました');
    }
}
