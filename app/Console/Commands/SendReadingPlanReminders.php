<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendReadingPlanReminders extends Command
{
    /**
     * コンソールコマンドの実行名
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * コマンドの説明
     */
    protected $description = '読書計画の期日に応じたリマインダー通知（期日経過時・3日前・当日・3日後）を送信します';

    /**
     * コマンドの実行処理
     *
     * @return int 終了コード（0: 成功）
     */
    public function handle(): int
    {
        $today = now()->startOfDay();

        $reminders = collect([
            ['timing' => ReminderTiming::OverDueDate, 'targetDate' => $today->copy()],
            ['timing' => ReminderTiming::ThreeDaysBefore, 'targetDate' => $today->copy()->addDays(3)],
            ['timing' => ReminderTiming::OnDueDate, 'targetDate' => $today->copy()],
            ['timing' => ReminderTiming::ThreeDaysAfter, 'targetDate' => $today->copy()->subDays(3)],
        ]);

        $sentCount = $reminders->sum(fn (array $reminder): int => $this->notifyPlansDueOn(
            $reminder['targetDate'],
            $reminder['timing'],
        ));

        $this->info("{$sentCount}件通知しました");

        return Command::SUCCESS;
    }

    /**
     * 該当する進行中読書計画に通知を送り、送信件数を返す
     *
     * @param  Carbon  $targetData  通知対象とする期日
     * @param  ReminderTiming  $timing  通知タイミング
     * @return int 送信した通知の件数
     */
    private function notifyPlansDueOn(Carbon $targetDate, ReminderTiming $timing): int
    {
        $isOverdue = $timing === ReminderTiming::OverDueDate;

        return ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->when(
                $isOverdue,
                // 期日経過時：期日が今日より前で、まだ通知していないものを抽出
                fn ($query) => $query->whereDate('target_date', '<', $targetDate)->whereNull('overdue_notified_at'),
                // それ以外：期日がちょうどその日のものを抽出
                fn ($query) => $query->whereDate('target_date', $targetDate),
            )
            ->get()
            ->each(function (ReadingPlan $plan) use ($timing, $isOverdue) {
                $plan->user->notify(new ReadingPlanReminder($plan, $timing));

                // 期日経過時は`overdue_notified_at`に通知した記録を残す
                if ($isOverdue) {
                    $plan->update(['overdue_notified_at' => now()]);
                }
            })
            ->count();
    }
}
