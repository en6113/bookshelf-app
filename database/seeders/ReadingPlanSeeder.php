<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            // 期日経過時（期日経過時の通知対象）
            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $userBookIds[2],
                'target_date' => $today->copy()->subDays(1),
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
            $targetUserBookIds = $bookIds->diff($usedBookIds)->random(5)->values();

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
            ];

            foreach ($addPlans as $plan) {
                ReadingPlan::factory()->create($plan);
            }

            // 自動失効バッチの確認用（保持期間30日超え）
            $expiredPlan = ReadingPlan::factory()->create([
                'user_id' => $targetUser->id,
                'book_id' => $targetUserBookIds[3],
                'target_date' => $today->copy()->subDays(32),
                'status' => ReadingPlanStatus::InProgress,
            ]);

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => ReadingPlanReminder::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $targetUser->id,
                'data' => json_encode([
                    'reading_plan_id' => $expiredPlan->id,
                    'title' => '期日が過ぎています',
                    'body' => "「{$expiredPlan->book->title}」の期日が過ぎました。(初期投入データ、保持期間30日超え、自動失効バッチで消える)",
                ]),
                'read_at' => null,
                'created_at' => $today->copy()->subDays(31),
                'updated_at' => $today->copy()->subDays(31),
            ]);

            // 自動失効バッチが送られないことの確認用（保持期間29日）
            $notExpiredPlan = ReadingPlan::factory()->create([
                'user_id' => $targetUser->id,
                'book_id' => $targetUserBookIds[4],
                'target_date' => $today->copy()->subDays(30),
                'status' => 'in_progress',
            ]);

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => ReadingPlanReminder::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $targetUser->id,
                'data' => json_encode([
                    'reading_plan_id' => $notExpiredPlan->id,
                    'title' => '期日が過ぎています',
                    'body' => "「{$notExpiredPlan->book->title}」の期日が過ぎました。(初期投入データ、保持期間29日、自動失効バッチで消えない)",
                ]),
                'read_at' => null,
                'created_at' => $today->copy()->subDays(29),
                'updated_at' => $today->copy()->subDays(29),
            ]);
        }
    }
}
