<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    protected string $timing;

    protected ReadingPlan $readingPlan;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReadingPlan $readingPlan, string $timing)
    {
        $this->readingPlan = $readingPlan;
        $this->timing = $timing;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $notificationData = match ($this->timing) {
            'over_due_date' => [
                'timing' => 'over_due_date',
                'title' => '期日が過ぎました',
                'body' => "「{$this->readingPlan->book->title}」の目標期日を超過しました。",
            ],
            'before_3_days' => [
                'timing' => 'three_days_before',
                'title' => 'まもなく期日です',
                'body' => "「{$this->readingPlan->book->title}」の目標期日まであと3日です!",
            ],
            'today' => [
                'timing' => 'on_due_date',
                'title' => '本日が期日です',
                'body' => "本日は「{$this->readingPlan->book->title}」の目標期日当日です!",
            ],
            'after_3_days' => [
                'timing' => 'three_days_after',
                'title' => '期日が過ぎています',
                'body' => "「{$this->readingPlan->book->title}」の目標期日から3日が経過しました。進捗はどうですか？",
            ],
            default => [
                'timing' => 'default',
                'title' => '読書計画リマインダー',
                'body' => '読書計画のリマインダーです。',
            ],
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $notificationData['timing'],
            'title' => $notificationData['title'],
            'body' => $notificationData['body'],
        ];
    }
}
