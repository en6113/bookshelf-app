<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーはお気に入り一覧画面を表示でき、自分のお気に入りだけが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $myBook = Book::factory()->create();
        $user->favoriteBooks()->attach($myBook);

        $otherUser = User::factory()->create();
        $otherBook = Book::factory()->create();
        $otherUser->favoriteBooks()->attach($otherBook);

        // Act
        $response = $this->actingAs($user)->get(route('favorites.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('books');

        $viewBooks = $response->viewData('books');
        $this->assertTrue($viewBooks->contains($myBook));
        $this->assertFalse($viewBooks->contains($otherBook));
    }

    /** @test */
    public function お気に入りが0件の場合もエラーにならず、お気に入り一覧画面が表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('favorites.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('books');
        $this->assertTrue($response->viewData('books')->isEmpty());
    }

    /** @test */
    public function お気に入り一覧が10件ごとにページネーションされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $books = Book::factory()->count(11)->create();
        $user->favoriteBooks()->attach($books);

        // Act & Assert(1ページ目の検証)
        $responsePage1 = $this->actingAs($user)->get(route('favorites.index', ['page' => 1]));

        $responsePage1->assertStatus(200);
        $responsePage1->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        // Act & Assert(2ページ目の検証)
        $responsePage2 = $this->actingAs($user)->get(route('favorites.index', ['page' => 2]));

        $responsePage2->assertStatus(200);
        $responsePage2->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });
    }

    /** @test */
    public function ユーザーは書籍をお気に入り登録することができ、書籍詳細画面にリダイレクトされる(): void
    {
        // Arrange & 確認
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        // Assert
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /** @test */
    public function 既にお気に入り登録している書籍に対して再度お気に入りを叩くと解除され、書籍詳細画面にリダイレクトされる(): void
    {
        // Arrange & 確認
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('favorites.toggle', $book));

        // Assert
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }
}
