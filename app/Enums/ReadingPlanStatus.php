<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * 各ステータスの日本語ラベル
     * * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::InProgress => '進行中',
            self::Completed => '読了',
        };
    }

    /**
     * 各ステータスのバッジの色
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::NotStarted => 'bg-gray-100 text-gray-800',
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-yellow-100 text-yellow-800',
        };
    }
}
