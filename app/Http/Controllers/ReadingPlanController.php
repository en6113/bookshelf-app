<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧表示
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        $currentStatus = $request->input('status');

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', auth()->id())
            ->ofStatus($currentStatus)
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面表示
     */
    public function create(): view
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画の保存
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        ReadingPlan::create($validated);

        return redirect(route('reading-plans.index'))->with('success', '読書計画を作成しました');
    }

    /**
     * 読書計画編集画面表示
     */
    public function edit(ReadingPlan $readingPlan)
    {
        return view('reading-plans.edit', $readingPlan);
    }

    /**
     * 読書計画の更新
     */
    public function update(StoreReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $validated = $request->validated();

        $readingPlan->update($validated);

        return redirect(route('reading-plans.index'))->with('success', '読書計画を更新しました');
    }

    /**
     * 読書計画の削除
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました');
    }

    /**
     * 読書計画の読了
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', 'この読書計画を読了にしました');
    }
}
