<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRankingControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ランキング一覧画面を表示でき、アクションから取得した書籍データがビューに渡される(): void
    {
        // Arrange
        $books = Book::factory()->count(3)->create();

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
}
