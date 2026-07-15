<?php

namespace App\Enums;

enum ReminderTiming: string
{
    case OverDueDate = 'over_due_date';
    case ThreeDaysBefore = 'three_days_before';
    case OnDueDate = 'on_due_date';
    case ThreeDaysAfter = 'three_days_after';

    /**
     * 通知のタイトルを返す
     *
     * @return string 通知タイトル
     */
    public function title(): string
    {
        return match ($this) {
            self::OverDueDate => '期日が過ぎています',
            self::ThreeDaysBefore => 'まもなく期日です',
            self::OnDueDate => '本日が期日です',
            self::ThreeDaysAfter => '期日から3日経過しました',
        };
    }

    /**
     * 通知の本文を返す
     *
     * @param  string  $bookTitle  対象書籍のタイトル
     * @return string 通知本文
     */
    public function body(string $bookTitle): string
    {
        return match ($this) {
            self::OverDueDate => "「{$bookTitle}」の期日を超過しました。",
            self::ThreeDaysBefore => "「{$bookTitle}」の目標期日まであと3日です!",
            self::OnDueDate => "本日は「{$bookTitle}」の目標期日当日です!",
            self::ThreeDaysAfter => "「{$bookTitle}」の目標期日から3日が経過しました。進捗はどうですか？",
        };
    }
}
