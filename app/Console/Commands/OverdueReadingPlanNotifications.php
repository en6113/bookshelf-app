<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;

class OverdueReadingPlanNotifications extends Command
{
    /**
     * コンソールコマンドの実行名
     *
     * @var string
     */
    protected $signature = 'notifications:overdue';

    /**
     * コマンドの説明
     */
    protected $description = '期日が過ぎた読書計画に対して通知を送信します';

    /**
     * コマンドの実行処理
     */
    public function handle()
    {
        $today = now()->startOfDay();

        $plans = ReadingPlan::where('status', 'in_progress')
            ->where('target_date', '=', $today->copy()->subDay(1))
            ->get();

        foreach ($plans as $plan) {
            $plan->user->notify(new ReadingPlanReminder($plan, 'over_due_date'));
        }

        $this->info("{$plans->count()}件通知しました");
    }
}
