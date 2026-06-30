<?php

namespace Tests\Unit\Actions;

use App\Actions\GetTopRatedBooksAction;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTopRatedBooksActionTest extends TestCase
{
    use RefreshDatabase;

    private Book $bestBook;

    private Book $mediumBook;

    private Book $worstBook;

    private GetTopRatedBooksAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bestBook = Book::factory()->create();
        $this->mediumBook = Book::factory()->create();
        $this->worstBook = Book::factory()->create();

        Review::factory()->create(['book_id' => $this->bestBook->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $this->mediumBook->id, 'rating' => 3]);

        $this->action = new GetTopRatedBooksAction;
    }

    /** @test */
    public function レビューの平均点が高い順に書籍が並び替わる(): void
    {
        // act
        $result = $this->action->execute();

        // assert
        $this->assertEquals($this->bestBook->id, $result->get(0)->id);
        $this->assertEquals($this->mediumBook->id, $result->get(1)->id);
        $this->assertEquals($this->worstBook->id, $result->get(2)->id);
    }

    /** @test */
    public function 指定した件数が取得できる(): void
    {
        // act
        $result = $this->action->execute(limit: 2);

        // assert
        $this->assertCount(2, $result);
    }

    /** @test */
    public function レビューが1件もない場合でも、エラーにならず空のデータが返る(): void
    {
        // arrange
        Book::query()->delete();

        // act
        $result = $this->action->execute();

        // assert
        $this->assertTrue($result->isEmpty());
    }
}
