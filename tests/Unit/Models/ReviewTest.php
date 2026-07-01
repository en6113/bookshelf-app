<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $this->assertTrue($review->user->is($user));
    }

    /** @test */
    public function test_review_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->for($book)->create();

        $this->assertTrue($review->book->is($book));
    }

    /** @test */
    public function test_review_belongs_to_many_liked_by_users(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();

        $review->likedByUsers()->attach($user->id);

        $review->load('likedByUsers');

        $this->assertTrue($review->likedByUsers->contains($user));
    }
}
