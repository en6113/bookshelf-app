<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 書籍一覧 (GET /books)
    // =========================================================================

    /** @test */
    public function 未ログインでも書籍一覧画面にアクセスできる(): void
    {
        // Act
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('books.index');
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

    /** @test */
    public function 書籍一覧でキーワード検索が機能する(): void
    {
        // Arrange
        $authorHitBooks = Book::factory()->count(3)->create(['author' => '夏目 漱石']);
        Book::factory()->create(['author' => '芥川 龍之介']);
        $titleHitBook = Book::factory()->create(['title' => '夏目友人帳']);

        $exceptedIds = $authorHitBooks->pluck('id')
            ->push($titleHitBook->id)
            ->toArray();

        // Act
        $response = $this->get(route('books.index', ['keyword' => '夏目']));

        // Assert
        $response->assertOk();

        $viewBooks = $response->viewData('books');
        $this->assertCount(4, $viewBooks);
        $this->assertEqualsCanonicalizing(
            $exceptedIds,
            $viewBooks->pluck('id')->toArray()
        );

        $response->assertSee('夏目 漱石');
        $response->assertSee('夏目友人帳');
        $response->assertDontSee('芥川 龍之介');
    }

    /** @test */
    public function 書籍一覧でジャンル検索が機能する(): void
    {
        // Arrange
        $genre = Genre::factory()->create();
        $books = Book::factory()->hasAttached($genre)->count(3)->create();
        $otherBook = Book::factory()->create(['title' => 'ジャンルが違う本']);

        // Act
        $response = $this->get(route('books.index', ['genre' => $genre->id]));

        // Assert
        $response->assertOk();
        $viewBooks = $response->viewData('books');
        $this->assertCount(3, $viewBooks);
        $this->assertEqualsCanonicalizing(
            $books->pluck('id')->toArray(),
            $viewBooks->pluck('id')->toArray(),
        );

        $response->assertDontSee('ジャンルが違う本');
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、新しい順で並んでいる(): void
    {
        // Arrange
        $oldBook = Book::factory()->create(['created_at' => now()->subDay()]);
        $newBook = Book::factory()->create(['created_at' => now()]);

        // Act
        $response = $this->get(route('books.index', ['sort' => 'newest']));

        // Assert
        $response->assertOk();

        $viewBooks = $response->viewData('books');
        $this->assertCount(2, $viewBooks);
        $this->assertEquals($newBook->id, $viewBooks[0]->id);
        $this->assertEquals($oldBook->id, $viewBooks[1]->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、古い順で並んでいる(): void
    {
        // Arrange
        $newBook = Book::factory()->create(['created_at' => now()]);
        $oldBook = Book::factory()->create(['created_at' => now()->subDay()]);

        // Act
        $response = $this->get(route('books.index', ['sort' => 'oldest']));

        // Assert
        $response->assertOk();

        $viewBooks = $response->viewData('books');
        $this->assertCount(2, $viewBooks);
        $this->assertEquals($oldBook->id, $viewBooks[0]->id);
        $this->assertEquals($newBook->id, $viewBooks[1]->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、タイトル順で並んでいる(): void
    {
        // Arrange
        $backBook = Book::factory()->create(['title' => 'それから']);
        $frontBook = Book::factory()->create(['title' => 'こころ']);

        // Act
        $response = $this->get(route('books.index', ['sort' => 'title']));

        // Assert
        $response->assertOk();

        $viewBooks = $response->viewData('books');
        $this->assertCount(2, $viewBooks);
        $this->assertEquals($frontBook->id, $viewBooks[0]->id);
        $this->assertEquals($backBook->id, $viewBooks[1]->id);
    }

    /** @test */
    public function 書籍一覧で並べ替えが機能し、レビューの平均評価が高い順で並んでいる(): void
    {
        // Arrange
        $lowRatingBook = Book::factory()->create();
        $highRatingBook = Book::factory()->create();

        Review::factory()->create(['book_id' => $lowRatingBook->id, 'rating' => 3]);
        Review::factory()->create(['book_id' => $highRatingBook->id, 'rating' => 5]);

        // Act
        $response = $this->get(route('books.index', ['sort' => 'rating']));

        // Assert
        $response->assertOk();

        $viewBooks = $response->viewData('books');
        $this->assertCount(2, $viewBooks);
        $this->assertEquals($highRatingBook->id, $viewBooks[0]->id);
        $this->assertEquals($lowRatingBook->id, $viewBooks[1]->id);
    }

    /** @test */
    public function 書籍一覧の検索機能でバリデーションエラー時は元のページにリダイレクトする(): void
    {
        $response = $this->get(route('books.index', ['genre' => 9999]));

        // Assert
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['genre']);
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
        $response->assertViewIs('books.show');
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
    // ISBN検索 (GET /books/isbn/{isbn})
    // =========================================================================

    /** @test */
    public function isb_n検索をすると書籍データがjson形式で取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $isbn = '9784101010014';

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'テスト書籍タイトル',
                            'authors' => ['テスト著者'],
                            'publishedDate' => '2026-01-01',
                            'description' => 'テストの概要説明',
                            'imageLinks' => ['thumbnail' => 'http://example.com/image.jpg'],
                        ],
                    ],
                ],
            ], Response::HTTP_OK),
        ]);

        // Act
        $response = $this->actingAs($user)->getJson('books/isbn/'.$isbn);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'title' => 'テスト書籍タイトル',
            'author' => 'テスト著者',
            'published_date' => '2026-01-01',
            'description' => 'テストの概要説明',
            'image_url' => 'http://example.com/image.jpg',
        ]);
    }

    /** @test */
    public function isb_n検索で書籍が見つからない時は404エラーとメッセージが返る(): void
    {
        // Arrange
        $user = User::factory()->create();
        $isbn = 1234567890123;

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                // itemsを含めない
            ], Response::HTTP_OK),
        ]);

        // Act
        $response = $this->actingAs($user)->get('books/isbn/'.$isbn);

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['error' => '該当する書籍が見つかりませんでした。']);
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
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前のタイトル',
        ]);
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
