<?php

namespace Tests\Feature\Console;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未読了の読書計画の期日3日前に予告リマインダーが送られる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->addDays(3)->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $this->artisan('notifications:send')->assertExitCode(0);

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
            'data->timing' => 'three_days_before',
        ]);
    }

    /** @test */
    public function 未読了の読書計画の当日に当日リマインダーが送られる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $this->artisan('notifications:send')->assertExitCode(0);

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
            'data->timing' => 'on_due_date',
        ]);
    }

    /** @test */
    public function 期日経過の読書計画に期日経過リマインダーが送られる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subDay()->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $this->artisan('notifications:send')->assertExitCode(0);

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
            'data->timing' => 'over_due_date',
        ]);
    }

    /** @test */
    public function 未完了の読書計画の期日3日後に再エンゲージメントリマインダーが送られる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->subDays(3)->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $this->artisan('notifications:send')->assertExitCode(0);

        // Assert
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
            'data->timing' => 'three_days_after',
        ]);
    }

    /** @test */
    public function 完了の読書計画には通知が送られないこと(): void
    {
        // Arrange
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => now()->addDays(3)->startOfDay(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        // Act
        $this->artisan('notifications:send')->assertExitCode(0);

        // Assert
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $plan->id,
        ]);
    }
}
