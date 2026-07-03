<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーはジャンル一覧画面にアクセスでき、ジャンル別の書籍数一覧が取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        // Act
        $response = $this->actingAs($user)->get(route('genres.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $viewGenre = $response->viewData('genres');
        $this->assertEquals(1, $viewGenre->first()->books_count);
    }

    /** @test */
    public function ユーザーはジャンル詳細画面にアクセスできる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        // Act
        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('books');
    }

    /** @test */
    public function ジャンル詳細画面は10件ごとにページネーションされる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();
        $genre->books()->attach($books->pluck('id'));

        // Act & Assert(1ページ目)
        $responsePage1 = $this->actingAs($user)->get(route('genres.show', [$genre, 'page' => 1]));
        $responsePage1->assertStatus(200);
        $responsePage1->assertViewHas('books', function ($books) {
            return $books->count() === 10;
        });

        // Act & Assert(2ページ目)
        $responsePage2 = $this->actingAs($user)->get(route('genres.show', [$genre, 'page' => 2]));
        $responsePage2->assertStatus(200);
        $responsePage2->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });
    }

    /** @test */
    public function ユーザーがジャンル登録画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    /** @test */
    public function ユーザーはジャンルを登録でき、ジャンル一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $storeData = ['name' => 'ジャンル名'];

        $response = $this->actingAs($user)->post(route('genres.store'), $storeData);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('genres', ['name' => 'ジャンル名']);
    }

    /** @test */
    public function ユーザーはジャンルを編集画面にアクセスできる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
    }

    /** @test */
    public function ユーザーはジャンルを更新でき、ジャンル一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => 'ジャンル名']);
        $updateData = ['name' => '更新後のジャンル名'];

        $response = $this->actingAs($user)->put(route('genres.update', $genre), $updateData);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後のジャンル名',
        ]);
    }

    /** @test */
    public function ユーザーは書籍と紐づいていないジャンルを削除でき、ジャンル一覧画面にリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '削除するジャンル名']);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '削除するジャンル名',
        ]);
    }

    /** @test */
    public function ユーザーは書籍が紐づいているジャンルは削除できず、元の画面にリダイレクトされる。(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $genre->books()->attach($book->id);

        // Act
        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
