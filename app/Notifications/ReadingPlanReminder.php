<?php

namespace App\Notifications;

use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 読書計画のリマインダー通知（DatabaseChannelに保存）
 */
class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * @param  ReadingPlan  $readingPlan  通知対象の読書計画
     * @param ReminderTiming  $timing  通知タイミング
     */
    public function __construct(protected ReadingPlan $readingPlan, protected ReminderTiming $timing){
    }

    /**
     * 通知の配信チャンネルを返す
     *
     * @param  object  $notifiable  通知先(Userモデル)
     * @return array<int, string>  配信チャンネルの配列
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Notificationsテーブルのdataカラムに保存する内容を返す
     *
     * @param  object  $notifiable  通知先(Userモデル)
     * @return array<string, mixed>  dataカラムに保存するデータ
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing->value,
            'title' => $this->timing->title(),
            'body' => $this->timing->body($this->readingPlan->book->title),
        ];
    }
}
