<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRankingControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ランキング一覧画面を表示でき、アクションから取得した書籍データがビューに渡される(): void
    {
        // Arrange
        $books = Book::factory()->count(3)->has(Review::factory())->create();

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');

        $viewBooks = $response->viewData('rankedBooks');
        $this->assertCount(3, $viewBooks);
        $this->assertTrue($viewBooks->contains($books->first()));
    }

    /** @test */
    public function ランキング一覧は評価が高い順に表示される(): void
    {
        // Arrange
        $highRatingBook = Book::factory()->create();
        $middleRatingBook = Book::factory()->create();
        $lowRatingBook = Book::factory()->create();

        Review::factory()->create(['book_id' => $highRatingBook->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $middleRatingBook->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $lowRatingBook->id, 'rating' => 1]);

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('rankedBooks');

        $viewBooks = $response->viewData('rankedBooks');
        $this->assertEquals($highRatingBook->id, $viewBooks->get(0)->id);
        $this->assertEquals($middleRatingBook->id, $viewBooks->get(1)->id);
        $this->assertEquals($lowRatingBook->id, $viewBooks->get(2)->id);
    }
}
