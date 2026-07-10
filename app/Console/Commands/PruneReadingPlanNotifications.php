<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneReadingPlanNotifications extends Command
{
    /**
     * コンソールコマンドの実行名
     *
     * @var string
     */
    protected $signature = 'notifications:prune';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '30日以上経過した通知を削除します。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = 30;

        DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
