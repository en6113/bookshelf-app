<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 書籍一覧API (GET /api/v1/books)
    // =========================================================================

    /** @test */
    public function 書籍一覧をjson形式で取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        Book::factory()->count(3)->hasAttached($genre)->create();

        // Act
        $response = $this->getJson('/api/v1/books');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'image_url',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'reviews_avg_rating',
                    'reviews_count',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $response->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function 書籍一覧でper_pageの指定がない場合、デフォルトで10件ずつページネーションされる(): void
    {
        Book::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonCount(10, 'data');
    }

    /** @test */
    public function 書籍一覧でper_pageが機能し、結果がページネーションされる(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books?per_page=20');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonCount(20, 'data');
    }

    /** @test */
    public function 書籍一覧でキーワード検索が機能する(): void
    {
        // Arrange
        Book::factory()->count(3)->create(['author' => '夏目 漱石']);
        Book::factory()->create(['author' => '芥川 龍之介']);

        // Act
        $response = $this->getJson('/api/v1/books?keyword=%夏目%');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment(['author' => '夏目 漱石']);
        $response->assertJsonMissing(['author' => '芥川 龍之介']);
    }

    /** @test */
    public function 書籍一覧でジャンル検索が機能する(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $books = Book::factory()->hasAttached($genre)->create();
        $otherBook = Book::factory()->create();

        // Act
        $response = $this->getJson('/api/v1/books?genre_id='.$genre->id);

        // Assert
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $books->first()->id]);
        $response->assertJsonMissing(['id' => $otherBook->id]);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、新しい順で並んでいる(): void
    {
        // Arrange
        $oldBook = Book::factory()->create(['created_at' => now()->subDay()]);
        $newBook = Book::factory()->create(['created_at' => now()]);

        // Act
        $response = $this->getJson('/api/v1/books?sort=latest');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newBook->id);
        $response->assertJsonPath('data.1.id', $oldBook->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、古い順で並んでいる(): void
    {
        // Arrange
        $newBook = Book::factory()->create(['created_at' => now()]);
        $oldBook = Book::factory()->create(['created_at' => now()->subDay()]);

        // Act
        $response = $this->getJson('/api/v1/books?sort=oldest');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $oldBook->id);
        $response->assertJsonPath('data.1.id', $newBook->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、タイトル順で並んでいる(): void
    {
        // Arrange
        $backBook = Book::factory()->create(['title' => 'それから']);
        $frontBook = Book::factory()->create(['title' => 'こころ']);

        // Act
        $response = $this->getJson('/api/v1/books?sort=title');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $frontBook->id);
        $response->assertJsonPath('data.1.id', $backBook->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、レビューの平均評価が高い順で並んでいる(): void
    {
        // Arrange
        $highRatingBook = Book::factory()->create();
        $lowRatingBook = Book::factory()->create();

        Review::factory()->create(['book_id' => $highRatingBook->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $lowRatingBook->id, 'rating' => 3]);

        // Act
        $response = $this->getJson('/api/v1/books?sort=rating');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $highRatingBook->id);
        $response->assertJsonPath('data.1.id', $lowRatingBook->id);
    }

    /** @test */
    public function 書籍一覧の検索機能でバリデーションエラー時は422が返る(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=999');

        // Assert
        $response->assertStatus(422);
    }

    // =========================================================================
    // 書籍詳細API (GET /api/v1/books/{book})
    // =========================================================================

    /** @test */
    public function 書籍詳細にアクセスするとjson形式の詳細が返る(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $genre = Genre::factory()->create(['name' => 'ジャンル名']);
        $book = Book::factory()->hasAttached($genre)->create(['title' => 'テストタイトル']);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'comment' => 'テストレビュー',
        ]);

        // Act
        $response = $this->getJson('/api/v1/books/'.$book->id);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'created_at',
                'updated_at',
                'reviews_avg_rating',
                'reviews_count',
                'reviews' => [
                    '*' => [
                        'user_name',
                        'rating',
                        'comment',
                        'created_at',
                    ],
                ],
            ],
        ]);
        $response->assertJsonPath('data.title', 'テストタイトル');
        $response->assertJsonPath('data.genres.0.name', 'ジャンル名');
        $response->assertJsonPath('data.reviews.0.comment', 'テストレビュー');
    }

    /** @test */
    public function 存在しないidで書籍詳細にアクセスすると404が返る(): void
    {
        $response = $this->getJson('/api/v1/books/9999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // 書籍登録API (POST /api/v1/books)
    // =========================================================================

    /** @test */
    public function 書籍を登録するとレコードが作成され201が返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();
        $validData = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2026/05/30',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        // Act
        $response = $this->actingAs($user)->postJson('/api/v1/books', $validData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.title', 'テストタイトル');
        $response->assertJsonPath('data.isbn', '1234567890123');
        $response->assertJsonCount(2, 'data.genres');

        $this->assertDatabaseHas('books', ['isbn' => '1234567890123']);

        $book = Book::where('isbn', '1234567890123')->first();

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    /** @test */
    public function 書籍登録時にバリデーションエラーがある場合は422が返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $invalidData = [
            'user_id' => $user->id,
            'title' => 'エラーテストのタイトル',
            'author' => '著者名',
            'isbn' => '1234567890456',
            'published_date' => 'error',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this->actingAs($user)->postJson('/api/v1/books', $invalidData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['published_date']);
        $this->assertDatabaseMissing('books', ['title' => 'エラーテストのタイトル']);
    }

    /** @test */
    public function トランザクション途中でエラーが発生した際、すべてロールバックされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $data = [
            'user_id' => $user->id,
            'title' => '例外テストのタイトル',
            'author' => '著者名',
            'isbn' => '1234567890789',
            'published_date' => '2026/05/30',
            'genres' => [$genre->id],
        ];

        // イベントの監視設定（book_genreにデータが入る瞬間に強制エラーを起こす）
        \DB::listen(function ($query) {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'book_genre')) {
                throw new \RuntimeException('中間テーブル保存時に通信エラー発生');
            }
        });

        // Act
        try {
            $this->actingAs($user)->postJson('/api/v1/books', $data);
        } catch (\RuntimeException $e) {
            // スルー
        }

        // Assert
        $this->assertDatabaseMissing('books', ['title' => '例外テストのタイトル']);

        // イベントの解除
        Event::forget('illuminate.query');
    }

    /** @test */
    public function 書籍登録時に定義外のパラメーターが送られても無視される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $validData = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890987',
            'published_date' => '2026/05/30',
            'genres' => [$genre->id],
        ];

        // 正しいデータに定義外のパラメータを混ぜる
        $requestData = array_merge($validData, [
            'spam' => 'spam_value',
        ]);

        // Act
        $response = $this->actingAs($user)->postJson('/api/v1/books', $requestData);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('books', ['isbn' => '1234567890987']);
    }

    // =========================================================================
    // 書籍更新API (PUT /api/v1/books/{book})
    // =========================================================================

    /** @test */
    public function 書籍の作成者本人は自身が作成した書籍を更新でき、レコードが更新され200が返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $myBook = Book::factory()->create(['user_id' => $user->id]);
        $newGenre = Genre::factory()->create(['name' => '更新後のジャンル名']);

        $updateData = array_merge($myBook->toArray(), [
            'title' => '更新後のタイトル',
            'genres' => [$newGenre->id],
        ]);

        // Act
        $response = $this->actingAs($user)->putJson('/api/v1/books/'.$myBook->id, $updateData);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.title', '更新後のタイトル');
        $response->assertJsonCount(1, 'data.genres');
        $response->assertJsonPath('data.genres.0.name', '更新後のジャンル名');
        $this->assertDatabaseHas('books', [
            'id' => $myBook->id,
            'title' => '更新後のタイトル',
        ]);
        $this->assertDatabaseHas('book_genre', [
            'book_id' => $myBook->id,
            'genre_id' => $newGenre->id,
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
        $response = $this->actingAs($user)->putJson('/api/v1/books/'.$otherBook->id, $updateData);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
            'title' => '更新前のタイトル',
        ]);
    }

    /** @test */
    public function 存在しないidで書籍を更新しようとすると404が返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $validData = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890987',
            'published_date' => '2026/05/30',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this->actingAs($user)->putJson('/api/v1/books/9999', $validData);

        // Assert
        $response->assertStatus(404);

    }

    // =========================================================================
    // 書籍削除API (DELETE /api/v1/books/{book})
    // =========================================================================

    /** @test */
    public function 書籍の作成者本人は自身が作成した書籍を削除でき、204が返りレコードが削除される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '自身が作成した書籍',
        ]);

        // Act
        $response = $this->actingAs($user)->deleteJson('/api/v1/books/'.$book->id);

        // Assert
        $response->assertStatus(204);
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

    /** @test */
    public function 存在しないidで書籍を削除しようとすると404が返る(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('api/v1/books/9999');

        $response->assertStatus(404);
    }
}
