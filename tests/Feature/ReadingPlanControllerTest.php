<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

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

    /** @test */
    public function ユーザーは読書計画作成画面にアクセスすることができる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.create');
    }

    /** @test */
    public function ユーザーは読書計画を作成でき、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $data = [
            'book_id' => $book->id,
            'target_date' => '2026/07/09',
        ];

        $response = $this->actingAs($user)->post(route('reading-plans.store'), $data);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** @test */
    public function 作成者本人は読書計画の編集画面にアクセスすることができる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.edit');
    }

    /** @test */
    public function 作成者本人は読書計画を更新することができ、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create([
            'target_date' => '2026-07-01',
        ]);
        $updateData = ['target_date' => '2026-08-01'];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), $updateData);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => '2026-08-01',
        ]);
        $this->assertDatabaseMissing('reading_plans', ['target_date' => '2026-07-01']);
        $response->assertRedirect(route('reading-plans.index'));
    }

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
    public function 作成者本人は読書計画の状態を「読了」に更新でき、完了日時が記録され、読書計画一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $knownDate = Carbon::now();
        $this->travelTo($knownDate);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => 'completed',
            'completed_at' => $knownDate->format('Y-m-d H:i:s'),
        ]);
    }

    /** @test */
    public function 他人の読書計画は更新できない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReadingPlan = ReadingPlan::factory()->for($otherUser)->create([
            'target_date' => '2026-07-10',
        ]);
        $updateData = ['target_date' => '2026-8-10'];

        $response = $this->actingAs($user)->put(route('reading-plans.update', $otherReadingPlan), $updateData);

        $response->assertStatus(403);
        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherReadingPlan->id,
            'target_date' => '2026-07-10',
        ]);
        $this->assertDatabaseMissing('reading_plans', [
            'target_date' => '2026-8-10',
        ]);
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
