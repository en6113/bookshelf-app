<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $bookIds = Book::inRandomOrder()->pluck('id');

        $today = Carbon::today();

        foreach ($users as $user) {
            $userBookIds = $bookIds->random(4)->values();

            // 期日経過時（期日経過時の通知対象）
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $userBookIds[2],
                'target_date' => $today->copy()->subDay(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

            // 期日が3日後（3日前予告の通知対象)
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $userBookIds[0],
                'target_date' => $today->copy()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ]);

            // 当日（期日当日の通知対象）
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $userBookIds[1],
                'target_date' => $today,
                'status' => ReadingPlanStatus::InProgress,
            ]);

            // 期日が3日前（3日後再エンゲージメントの通知の対象）
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $userBookIds[3],
                'target_date' => $today->copy()->subDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ]);
        }

        // 主要シナリオ用の追加の読書計画
        $targetUser = User::first();

        if ($targetUser) {
            $usedBookIds = ReadingPlan::where('user_id', $targetUser->id)->pluck('book_id');
            $targetUserBookIds = $bookIds->diff($usedBookIds)->random(4)->values();

            $addPlans = [
                // 期日が3日後（読了の計画には3日前予告通知が送られないことの確認用)
                [
                    'user_id' => $targetUser->id,
                    'book_id' => $targetUserBookIds[0],
                    'target_date' => $today->copy()->addDays(3),
                    'completed_at' => $today->copy()->subDay(1),
                    'status' => ReadingPlanStatus::Completed,
                ],
                // 当日（読了の計画には期日当日通知が送られないことの確認用）
                [
                    'user_id' => $targetUser->id,
                    'book_id' => $targetUserBookIds[1],
                    'target_date' => $today,
                    'completed_at' => $today->copy()->subDay(2),
                    'status' => ReadingPlanStatus::Completed,
                ],
                // 期日が3日前（読了の計画には3日後再エンゲージメント通知が送られないことの確認用）
                [
                    'user_id' => $targetUser->id,
                    'book_id' => $targetUserBookIds[2],
                    'target_date' => $today->copy()->subDays(3),
                    'completed_at' => $today->copy()->subDay(3),
                    'status' => ReadingPlanStatus::Completed,
                ],
                // 期日が2日前で期限切れ（期限切れ通知が重ねて送られないことの確認用）
                [
                    'user_id' => $targetUser->id,
                    'book_id' => $targetUserBookIds[3],
                    'target_date' => $today->copy()->subDays(2),
                    'status' => ReadingPlanStatus::Expired,
                ],
            ];

            foreach ($addPlans as $plan) {
                ReadingPlan::factory()->create($plan);
            }
        }
    }
}
