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
    public function 未認証ユーザーはレビュー投稿をしようとするとログインページにリダイレクトされる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        // Act
        $response = $this->post(route('reviews.store', $book));

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

    /** @test */
    public function 未認証ユーザーは書籍登録にアクセスできずログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('books.create'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはお気に入りにアクセスできずログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('favorites.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはジャンル管理にアクセスできずログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('genres.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーはマイレポートにアクセスできずログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('reports.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーは読書計画にアクセスできずログインページにリダイレクトされる(): void
    {
        // Act
        $response = $this->get(route('reading-plans.index'));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 未認証ユーザーは通知一覧にアクセスできずログインページにリダイレクトされる(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }
}
