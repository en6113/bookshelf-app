<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneReadingPlanNotifications extends Command
{
    /**
     * 通知の保持日数
     */
    private const RETENTION_DAYS = 30;

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
     * コマンドの実行処理
     *
     * @return int 終了コード（0: 成功）
     */
    public function handle(): int
    {
        $deletedCount = DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();

        $this->info("{$deletedCount}件の通知を削除しました");

        return Command::SUCCESS;
    }
}
