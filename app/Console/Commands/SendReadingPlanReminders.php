<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

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
    protected $description = '未読了の読書計画に対して、3日前・当日・期日経過時・3日後の20時に通知を送信します';

    /**
     * コマンドの実行処理
     */
    public function handle()
    {
        $today = now()->startOfDay();

        // 3日前リマインダー用
        $before3DaysPlans = ReadingPlan::where('target_date', $today->copy()->addDays(3))
            ->where('status', ReadingPlanStatus::InProgress)
            ->get();
        foreach ($before3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'before_3_days'));
        }

        // 当日リマインダー用
        $todayPlans = ReadingPlan::where('target_date', $today)
            ->where('status', ReadingPlanStatus::InProgress)
            ->get();
        foreach ($todayPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'today'));
        }

        // 期日経過時リマインダー用
        $overDuePlans = ReadingPlan::where('target_date', '<', $today)
            ->where('target_date', '>=', $today->copy()->subDays(2))
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereNull('overdue_notified_at')
            ->get();
        foreach ($overDuePlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'over_due_date'));
            $plan->update(['overdue_notified_at' => now()]);
        }

        // 3日後リマインダー用
        $after3DaysPlans = ReadingPlan::where('target_date', $today->copy()->subDays(3))
            ->where('status', ReadingPlanStatus::InProgress)
            ->get();
        foreach ($after3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'after_3_days'));
        }
    }
}
