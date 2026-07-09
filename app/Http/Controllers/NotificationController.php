<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Notifications\DatabaseNotification;

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
    public function read(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notidiable_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return redirect()->route('notifications.index')->with('success', '通知を既読にしました');
    }
}
