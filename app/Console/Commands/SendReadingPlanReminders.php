<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;

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
    protected $description = '未読了の読書計画に対して、3日前・当日・3日後の20時に通知を送信します';

    /**
     * コマンドの実行処理
     */
    public function handle()
    {
        $today = now()->startOfDay();

        // 3日前リマインダー用
        $before3DaysPlans = ReadingPlan::where('target_date', $today->copy()->addDays(3))
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($before3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'before_3_days'));
        }

        // 当日リマインダー用
        $todayPlans = ReadingPlan::where('target_date', $today)
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($todayPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'today'));
        }

        // 3日後リマインダー用
        $after3DaysPlans = ReadingPlan::where('target_date', $today->copy()->subDays(3))
            ->where('status', '!=', 'Completed')
            ->get();
        foreach ($after3DaysPlans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'after_3_days'));
        }
    }
}
