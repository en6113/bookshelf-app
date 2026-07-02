<?php

namespace Tests\Feature\Auth;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthenticatedRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未認証ユーザーは書籍登録にアクセスするとログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('books.create'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはお気に入りにアクセスするとログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('favorites.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはジャンル管理にアクセスするとログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('genres.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはお気に入り登録をしようとするとログインページにリダイレクトされる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->post(route('favorites.toggle', $book));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはいいねをしようとするとログインページにリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this->post(route('reviews.like', $review));

        // Assert
        $response->assertRedirect(route('login'));
    }
}
