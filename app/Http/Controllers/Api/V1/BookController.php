<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookIndexResource;
use App\Http\Resources\BookShowResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンル検索
        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        // 並び順検索
        $sortEnum = BookSort::tryFrom($request->input('sort')) ?? BookSort::LATEST;
        $query = $sortEnum->apply($query);

        // ページネーション
        $perPage = $request->input('per_page', 10);
        $books = $query->paginate($perPage);

        return BookIndexResource::collection($books);
    }

    /**
     * 書籍詳細を取得
     */
    public function show(Book $book): BookShowResource
    {
        $book->load(['genres', 'reviews']);
        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');

        return new BookShowResource($book);
    }

    /**
     * 書籍を新規登録
     */
    public function store(StoreBookRequest $request): BookShowResource
    {
        $validated = $request->validated();
        $genres = $validated['genres'];
        unset($validated['genres']);

        return DB::transaction(function () use ($validated, $genres) {
            $book = Book::create($validated);
            $book->genres()->attach($genres);
            $book->load(['genres']);

            return new BookShowResource($book);
        });
    }

    /**
     * 書籍を更新
     */
    public function update(UpdateBookRequest $request, Book $book): BookShowResource
    {
        $this->authorize('update', $book);
        $validated = $request->validated();
        $genres = $validated['genres'];
        unset($validated['genres']);

        return DB::transaction(function () use ($book, $validated, $genres) {
            $book->update($validated);
            $book->genres()->sync($genres ?? []);

            $book->load('genres');

            return new BookShowResource($book);
        });
    }

    /**
     * 書籍を削除
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        if (! $book) {
            return response()->ison([
                'message' => '指定された書籍が見つかりませんでした',
                'error_code' => 'BOOK_NOT_FOUND',
            ], 404);
        }

        $book->delete();

        return response()->json(null, 204);
    }
}
