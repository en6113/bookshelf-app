<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧表示
     */
    public function index(IndexReadingPlanRequest $request): View
    {
        $currentStatus = $request->validated()['status'] ?? null;

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', auth()->id())
            ->ofStatus($currentStatus)
            ->oldest('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成画面表示
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画の保存
     */
    public function store(ReadingPlanRequest $request): RedirectResponse
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
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の更新(過去日への期日変更はバリデーションで弾く)
     */
    public function update(ReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $validated['status'] = ReadingPlanStatus::InProgress;
        }

        $readingPlan->update($validated);

        return redirect(route('reading-plans.index'))->with('success', '読書計画を更新しました');
    }

    /**
     * 読書計画の削除
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました');
    }

    /**
     * 読書計画の読了
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', 'ステータスを読了に変更しました');
    }
}
