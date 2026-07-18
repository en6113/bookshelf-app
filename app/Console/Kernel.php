<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * 通知とステータス変更をスケジュールする処理
     * ステータスが通知の判断基準になっているため、sendをexpiredより先に実行すること（順番を変えないこと）
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notifications:send')->dailyAt('20:00');
        $schedule->command('reading-plans:expired')->dailyAt('20:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
