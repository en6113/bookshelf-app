<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 読書計画一覧 (GET /reading-plans)
    // =========================================================================

    /** @test */
    public function ユーザーは読書計画一覧にアクセスすることができる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->count(3)->create();

        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertStatus(200);
        $response->assertViewHas('readingPlans');
        $viewReadingPlan = $response->viewData('readingPlans');
        $this->assertCount(3, $viewReadingPlan);
        $this->assertTrue($viewReadingPlan->contains($readingPlan->first()));
        $this->assertFalse($viewReadingPlan->contains($otherReadingPlan));
    }

    // =========================================================================
    // 読書計画作成画面 (GET /reading-plans/create)
    // =========================================================================

    /** @test */
    public function ユーザーは読書計画作成画面にアクセスすることができる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.create');
    }

    // =========================================================================
    // 読書計画作成 (POST /reading-plans)
    // =========================================================================

    /** @test */
    public function ユーザーは読書計画を作成でき、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $data = [
            'book_id' => $book->id,
            'target_date' => now()->addDays(7),
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // =========================================================================
    // 読書計画編集画面 (GET /reading-plans/{reading_plan}/edit)
    // =========================================================================

    /** @test */
    public function 作成者本人は進行中の読書計画の編集画面にアクセスすることができる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.edit');
    }

    /** @test */
    public function 作成者本人は期限切れの読書計画の編集画面にアクセスすることができる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->subDay()->startOfDay(),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.edit');
    }

    /** @test */
    public function 作成者本人は完了済の読書計画の編集画面にアクセスすることができない(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->subDay()->startOfDay(),
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(403);
    }

    /** @test */
    public function 他人の読書計画の編集画面にアクセスすることはできない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $otherReadingPlan));

        $response->assertStatus(403);
    }

    // =========================================================================
    // 読書計画更新 (PUT /reading-plans/{reading_plan})
    // =========================================================================

    /** @test */
    public function 読書計画更新時に期日に過去の日付を入力するとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->addDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $updateData = ['target_date' => now()->subDays(2)];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), $updateData);

        $response->assertSessionHasErrors(['target_date']);
    }

    /** @test */
    public function 作成者本人は進行中の読書計画を更新することができ、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->addDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $updateData = ['target_date' => now()->addDays(5)];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), $updateData);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => now()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $this->assertDatabaseMissing('reading_plans', ['target_date' => now()->addDay()]);
    }

    /** @test */
    public function 作成者本人は期限切れの読書計画を更新することができ、ステータスが進行中に変更される(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->subDays(2),
            'status' => ReadingPlanStatus::Expired,
        ]);
        $updateData = ['target_date' => now()->addDays(5)];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), $updateData);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => now()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);
        $this->assertDatabaseMissing('reading_plans', ['target_date' => now()->subDays(2)]);
        $response->assertRedirect(route('reading-plans.index'));
    }

    /** @test */
    public function 作成者本人でも完了済みの読書計画を更新することはできない(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => now()->subDays(2),
            'status' => ReadingPlanStatus::Completed,
        ]);
        $updateData = ['target_date' => now()->addDays(5)];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function 他人の読書計画は更新できない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create([
            'target_date' => now()->addDay(),
        ]);
        $updateData = ['target_date' => now()->addDays(5)];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $otherReadingPlan), $updateData);

        $response->assertStatus(403);
        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherReadingPlan->id,
            'target_date' => now()->addDay(),
        ]);
    }

    // =========================================================================
    // 読書計画削除 (DELETE /reading-plans/{reading_plan})
    // =========================================================================

    /** @test */
    public function 作成者本人は読書計画を削除することができ、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $readingPlan));

        $this->assertDatabaseMissing('reading_plans', ['id' => $readingPlan->id]);
        $response->assertRedirect(route('reading-plans.index'));
    }

    /** @test */
    public function 他人の読書計画を削除することができない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $otherReadingPlan));

        $response->assertStatus(403);
        $this->assertDatabaseHas('reading_plans', ['id' => $otherReadingPlan->id]);
    }

    // =========================================================================
    // 読書計画完了 (POST /reading-plans/{reading_plan}/complete)
    // =========================================================================

    /** @test */
    public function 作成者本人は読書計画の状態を「完了」に更新でき、完了日時が記録され、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $knownDate = Carbon::now();
        $this->travelTo($knownDate);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => $knownDate->format('Y-m-d H:i:s'),
        ]);
    }

    /** @test */
    public function 他人の読書計画は状態を「読了」に更新できない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $otherReadingPlan));

        $response->assertStatus(403);
        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherReadingPlan->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }
}
