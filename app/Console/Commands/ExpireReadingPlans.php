<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class ExpireReadingPlans extends Command
{
    /**
     * コンソールコマンドの実行名
     *
     * @var string
     */
    protected $signature = 'reading-plans:expired';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '期日を経過した読書計画のステータスを期限切れに変更します';

    /**
     * コマンドの実行処理
     *
     * @return int 終了コード（0: 成功）
     */
    public function handle(): int
    {
        $expiredCount = ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', now())
            ->update(['status' => ReadingPlanStatus::Expired]);

        $this->info("{$expiredCount}件の計画のステータスを期限切れに変更しました");

        return Command::SUCCESS;
    }
}
