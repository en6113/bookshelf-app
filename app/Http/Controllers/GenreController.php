<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル管理一覧表示
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル詳細画面表示
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル登録画面表示
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンル保存
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Genre::create($validated);

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました');
    }

    /**
     * ジャンル編集画面表示
     */
    public function edit(Genre $genre): View
    {

        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンル更新
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validated();

        $genre->update($validated);

        return redirect()->route('genres.index')->with('success', 'ジャンル名を更新しました');
    }

    /**
     * ジャンル削除
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if($genre->books()->exists()) {
            return redirect()->back()->with('error', 'このジャンルには書籍が登録されているため削除できません');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました');
    }
}
