<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /**
     * 読書計画を読了にできるか
     */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書計画を更新できるか
     */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }

    /**
     * 読書計画を削除できるか
     */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $user->id === $readingPlan->user_id;
    }
}
