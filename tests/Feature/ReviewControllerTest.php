<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーはレビューを投稿でき、データベースに保存され、書籍詳細画面にリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $storeData = [
            'rating' => 5,
            'comment' => 'テスト用のレビュー内容です',
        ];

        // Act
        $response = $this->actingAs($user)->post(route('reviews.store', $book), $storeData);

        // Assert
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'テスト用のレビュー内容です',
        ]);
    }

    /** @test */
    public function 投稿者本人は編集画面にアクセスできる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        // Act
        $response = $this->actingAs($user)->get(route('reviews.edit', $review));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('reviews.edit');
    }

    /** @test */
    public function 他人のレビュー編集画面にはアクセスできず、403エラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherReview = Review::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('reviews.edit', $otherReview));

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function 投稿者本人はレビューを更新でき、データベースに保存され、書籍詳細画面にリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);
        $updateData = [
            'rating' => 4,
            'comment' => '更新後のコメント',
        ];

        // Act
        $response = $this->actingAs($user)->put(route('reviews.update', $review), $updateData);

        // Assert
        $response->assertRedirect(route('books.show', $review->book_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新後のコメント',
        ]);
    }

    /** @test */
    public function 他人のレビューを更新しようとした場合、403エラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReview = Review::factory()->create([
            'user_id' => $otherUser->id,
            'comment' => '他人のレビュー',
        ]);
        $updateData = [
            'rating' => 5,
            'comment' => '更新しようとしたレビュー',
        ];

        // Act
        $response = $this->actingAs($user)->put(route('reviews.update', $otherReview), $updateData);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $otherUser->id,
            'comment' => '他人のレビュー',
        ]);
        $this->assertDatabaseMissing('reviews', ['comment' => '更新しようとしたレビュー']);
    }

    /** @test */
    public function 投稿者本人はレビューを削除でき、書籍詳細画面にリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'comment' => '削除テスト用のコメント',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        // Assert
        $response->assertRedirect(route('books.show', $review->book_id));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '削除テスト用のコメント',
        ]);
    }

    /** @test */
    public function 他人のレビューを削除しようとした場合、403エラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherReview = Review::factory()->create([
            'user_id' => $otherUser->id,
            'comment' => '他人のレビュー',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(route('reviews.destroy', $otherReview));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $otherUser->id,
            'comment' => '他人のレビュー',
        ]);
    }

    /** @test */
    public function ユーザーはレビューにいいねをつけることができ、元の画面にリダイレクトされる(): void
    {
        // Arrange & 確認
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        // Assert
        $response->assertRedirect(route('books.show', $review->book_id));
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /** @test */
    public function 既にいいねしているレビューに対して再度いいねを叩くと解除される(): void
    {
        // Arrange & 確認
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $user->likedReviews()->attach($review);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // Act
        $response = $this->actingAs($user)->post(route('reviews.like', $review));

        // Assert
        $response->assertRedirect(route('books.show', $review->book_id));
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }
}
