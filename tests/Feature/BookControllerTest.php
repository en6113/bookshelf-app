<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 書籍一覧 (GET /books)
    // =========================================================================

    /** @test */
    public function 未ログインでも書籍一覧画面にアクセスでき、書籍一覧が最新順で取得できる(): void
    {
        // Arrange
        $oldBook = Book::factory()->create(['created_at' => now()->subDay()]);
        $newBook = Book::factory()->create(['created_at' => now()]);

        // Act
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('books');

        $viewBooks = $response->viewData('books');
        $this->assertEquals($newBook->id, $viewBooks->first()->id);
    }

    /** @test */
    public function 書籍一覧が10件ごとにページネーションされる(): void
    {
        // Arrange
        $Books = Book::factory()->count(11)->create();

        // Act & Assert(1ページ目の検証)
        $responsePage1 = $this->get(route('books.index', ['page' => 1]));

        $responsePage1->assertStatus(200);
        $responsePage1->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        // Act & Assert(2ページ目の検証)
        $responsePage2 = $this->get(route('books.index', ['page' => 2]));

        $responsePage2->assertStatus(200);
        $responsePage2->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });
    }

    // =========================================================================
    // 書籍詳細 (GET /books/{book})
    // =========================================================================

    /** @test */
    public function 未ログインでも書籍詳細画面にアクセスでき、200が返る(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
    }

    /** @test */
    public function 書籍の詳細情報が正しく取得できている(): void
    {
        // Arrange
        $book = Book::factory()->create();
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => 'ジャンル名']);
        $book->genres()->attach($genre);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'comment' => 'レビューのコメント',
        ]);
        $review->likedByUsers()->attach($user->id);

        // Act
        $response = $this->get(route('books.show', $book));

        // Assert
        $response->assertStatus(200);
        $viewBook = $response->viewData('book');
        $this->assertEquals($book->id, $viewBook->id);

        // EagerLoadの検証
        $this->assertTrue($viewBook->relationLoaded('genres'));
        $this->assertTrue($viewBook->relationLoaded('reviews'));

        // リレーション先のデータ整合性を検証
        $this->assertEquals('ジャンル名', $viewBook->genres->first()->name);
        $this->assertEquals('レビューのコメント', $viewBook->reviews->first()->comment);
        $this->assertEquals(1, $viewBook->reviews->first()->liked_by_users_count);
    }

    /** @test */
    public function 書籍にレビューが1件もない場合も200が返る(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
    }

    // =========================================================================
    // 書籍作成 (POST /books/{book})
    // =========================================================================

    /** @test */
    public function 書籍を作成するとデータが保存され一覧画面にリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2026/05/30',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this->actingAs($user)->post(route('books.store'), $data);

        // Assert
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', ['title' => 'テストタイトル']);
        $this->assertDatabaseHas('book_genre', ['genre_id' => $genre->id]);
    }

    /** @test */
    public function 書籍作成時にバリデーションエラーがある場合は元の画面にリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => 'error',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $data);

        // Assert
        $response->assertRedirect(route('books.create'));
        $response->assertSessionHasErrors(['published_date']);
        $this->assertDatabaseMissing('books', ['title' => 'テストタイトル']);
    }

    /** @test */
    public function 書籍作成のトランザクション途中でエラーが発生した際はすべてロールバックされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'user_id' => $user->id,
            'title' => '例外テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2026/05/30',
            'genres' => [$genre->id],
        ];

        // 中間テーブル（book_genre）にデータが入る瞬間に強制エラーを起こす
        \DB::listen(function ($query) {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'book_genre')) {
                throw new \RuntimeException('中間テーブル保存時に通信エラー発生');
            }
        });

        // Act
        try {
            $this->actingAs($user)->post(route('books.store'), $data);
        } catch (\RuntimeException $e) {
            // 意図通りの例外のためスルー
        }

        // Assert
        $this->assertDatabaseMissing('books', ['title' => '例外テストタイトル']);
    }

    // =========================================================================
    // 書籍編集 (GET /books/{book}/edit)
    // =========================================================================

    /** @test */
    public function 書籍の作成者本人は編集画面にアクセスでき、200が返る(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('books.edit', $book));

        $response->assertStatus(200);
    }

    /** @test */
    public function 他人の書籍の編集画面にはアクセスできず、403が返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherBook = Book::factory()->create(['user_id' => $otherUser->id]);

        // Act
        $response = $this->actingAs($user)->get(route('books.edit', $otherBook));

        // Assert
        $response->assertStatus(403);
    }

    // =========================================================================
    // 書籍更新 (PUT /books/{book})
    // =========================================================================

    /** @test */
    public function 書籍の作成者本人は自身が作成した書籍を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $genre = Genre::factory()->create();

        $updateData = array_merge($book->toArray(), [
            'title' => '更新後のタイトル',
            'genres' => [$genre->id],
        ]);

        // Act
        $response = $this->actingAs($user)->put(route('books.update', $book), $updateData);

        // Assert
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
        ]);
    }

    /** @test */
    public function 他人が作成した書籍を更新しようとした場合、403が返り、更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $otherBook = Book::factory()->create([
            'user_id' => $otherUser->id,
            'title' => '更新前のタイトル',
        ]);

        $updateData = array_merge($otherBook->toArray(), [
            'title' => '更新しようとしたタイトル',
            'genres' => [$genre->id],
        ]);

        // Act
        $response = $this->actingAs($user)->put(route('books.update', $otherBook), $updateData);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
            'title' => '更新前のタイトル',
        ]);
    }

    // =========================================================================
    // 書籍削除 (DELETE /books/{book})
    // =========================================================================

    /** @test */
    public function 書籍の作成者本人は自身が作成した書籍を削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '自身が作成した書籍',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        // Assert
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '自身が作成した書籍',
        ]);
    }

    /** @test */
    public function 他人が作成した書籍を削除しようとした場合、403が返り、削除できない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherBook = Book::factory()->create([
            'user_id' => $otherUser->id,
            'title' => '他人が作成した書籍',
        ]);

        // Act
        $response = $this->actingAs($user)->delete(route('books.destroy', $otherBook));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
            'title' => '他人が作成した書籍',
        ]);
    }
}
