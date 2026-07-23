<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_book_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->assertTrue($book->user->is($user));
    }

    /** @test */
    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->pluck('id'));

        $book->load('genres');

        $this->assertCount(2, $book->genres);
        $this->assertTrue($book->genres->pluck('id')->contains($genres->first()->id));
    }

    /** @test */
    public function test_book_has_many_reviews(): void
    {
        $book = Book::factory()->create();
        $reviews = Review::factory()->for($book)->count(2)->create();

        $this->assertCount(2, $book->reviews);
        $this->assertTrue($book->reviews->contains($reviews->first()));
    }

    /** @test */
    public function test_book_belongs_to_many_favorite_by_users(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $book->favoriteByUsers()->attach($user->id);

        $book->load('favoriteByUsers');

        $this->assertTrue($book->favoriteByUsers->contains($user));
    }

    /** @test */
    public function test_book_has_many_reading_plans(): void
    {
        $book = Book::factory()->create();
        $readingPlans = ReadingPlan::factory()->for($book)->count(2)->create();

        $this->assertCount(2, $book->readingPlans);
        $this->assertTrue($book->readingPlans->contains($readingPlans->first()));
    }
}
