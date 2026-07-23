<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧画面表示
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->latest()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする
     */
    public function read(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $this->authorize('read', $notification);

        $notification->markAsRead();

        return redirect()->route('notifications.index')->with('success', '通知を既読にしました');
    }
}
