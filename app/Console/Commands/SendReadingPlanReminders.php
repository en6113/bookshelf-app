<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    /**
     * コンソールコマンドの実行名
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * コマンドの説明
     */
    protected $description = '未読了の読書計画に対して、3日前・当日・3日後に通知を送信します';

    /**
     * コマンドの実行処理
     */
    public function handle()
    {
        $today = now()->startOfDay();

        // 3日前リマインダー
        $before3DaysPlans = ReadingPlan::where('target_date', $today->copy()->addDays(3))
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($before3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'before_3_days'));
        }

        // 当日リマインダー
        $todayPlans = ReadingPlan::where('target_date', $today)
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($todayPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'today'));
        }

        // 3日後リマインダー
        $after3DaysPlans = ReadingPlan::where('target_date', $today->copy()->subDays(3))
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($after3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'after_3_days'));
        }
    }
}
