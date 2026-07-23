<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->for($user)->count(2)->create();

        $this->assertCount(2, $user->books);
        $this->assertTrue($user->books->contains($books->first()));
    }

    /** @test */
    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->for($user)->count(2)->create();

        $this->assertCount(2, $user->reviews);
        $this->assertTrue($user->reviews->contains($reviews->first()));
    }

    /** @test */
    public function test_user_has_many_reading_plans(): void
    {
        $user = User::factory()->create();
        $readingPlans = ReadingPlan::factory()->for($user)->count(2)->create();

        $this->assertCount(2, $user->readingPlans);
        $this->assertTrue($user->readingPlans->contains($readingPlans->first()));
    }

    /** @test */
    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $user->load('favoriteBooks');

        $this->assertTrue($user->favoriteBooks->contains($book));
    }

    /** @test */
    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = User::factory()->create();
        $reviews = Review::factory()->count(2)->create();

        $user->likedReviews()->attach($reviews->pluck('id'));

        $user->load('likedReviews');

        $this->assertCount(2, $user->likedReviews);
        $this->assertTrue($user->likedReviews->pluck('id')->contains($reviews->first()->id));
    }
}
