<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビュー保存
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['book_id'] = $book->id;

        Review::create($validated);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました');
    }

    /**
     * レビュー編集画面表示
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビュー更新
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update($validated);

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを更新しました');
    }

    /**
     * レビュー削除
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->route('books.show', $review->book_id)->with('success', 'レビューを削除しました');
    }

    /**
     * いいねのトグル処理
     */
    public function toggle(Review $review): RedirectResponse
    {
        $review->toggleLike();

        return redirect()->route('books.show', $review->book_id);
    }
}
