<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_reading_plan_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($user)->create();

        $this->assertTrue($readingPlan->user->is($user));
    }

    /** @test */
    public function test_reading_plan_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::factory()->for($book)->create();

        $this->assertTrue($readingPlan->book->is($book));
    }
}
