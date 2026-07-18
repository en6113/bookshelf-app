<?php

namespace Tests\Feature\Console;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 進行中の読書計画は、期日を過ぎたらステータスが期限切れに変更される(): void
    {
        $expiredPlan = ReadingPlan::factory()->create([
            'target_date' => now()->subDay()->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $progressPlan = ReadingPlan::factory()->create([
            'target_date' => now()->addDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:expired')->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'status' => ReadingPlanStatus::Expired,
        ]);
        $this->assertDatabaseHas('reading_plans', [
            'id' => $progressPlan->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }

    /** @test */
    public function 期限当日の進行中の読書計画は、ステータスが変更されない(): void
    {
        $onDuePlan = ReadingPlan::factory()->create([
            'target_date' => now()->startOfDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->artisan('reading-plans:expired')->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $onDuePlan->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }

    /** @test */
    public function 読了した読書計画は、期限が過ぎてもステータスは変更されない(): void
    {
        $completedPlan = ReadingPlan::factory()->create([
            'target_date' => now()->subDay()->startOfDay(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $this->artisan('reading-plans:expired')->assertExitCode(0);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $completedPlan->id,
            'status' => ReadingPlanStatus::Completed,
        ]);
    }
}
