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
            'before_3_days' => [
                'timing' => 'three_days_before',
                'body' => "「{$this->readingPlan->book->title}」の目標期日まであと3日です!",
            ],
            'today' => [
                'timing' => 'on_due_date',
                'body' => "本日は「{$this->readingPlan->book->title}」の目標期日当日です！",
            ],
            'after_3_days' => [
                'timing' => 'three_days_after',
                'body' => "「{$this->readingPlan->book->title}」の目標期日から3日が経過しました。進捗はどうですか？",
            ],
            default => [
                'timing' => 'default',
                'body' => '読書計画のリマインダーです。',
            ],
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $notificationData['timing'],
            'title' => '読書計画リマインダー',
            'body' => $notificationData['body'],
        ];
    }
}
