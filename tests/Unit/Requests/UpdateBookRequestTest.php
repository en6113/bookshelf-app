<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data, ?int $bookId = null): \Illuminate\Validation\Validator
    {
        $request = new UpdateBookRequest;

        // どの書籍（ID）を更新しようとしているか疑似的にルートパラメーターを設定
        if ($bookId) {
            $route = new Route('PUT', '/books/{book}', []);
            $route->bind(new Request); // バウンドの初期化
            $route->setParameter('book', $bookId);

            $request->setRouteResolver(fn () => $route);
        }

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '9876543210123',
            'published_date' => '2026/05/30',
            'description' => '更新後の書籍の説明',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=999',
        ], $overrides);
    }

    /** @test */
    public function 更新時にisbnが自分自身のレコードと重複していてもバリデーションエラーにならない(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $existingBook = Book::factory()->create(['isbn' => '9876543210123']);

        $updateData = $this->validData($user, [
            'isbn' => $existingBook->isbn,
            'genres' => $genres->pluck('id')->toArray(),
        ]);

        // Act
        $validator = $this->validator($updateData, $existingBook->id);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 更新時にisbnが他のレコードと重複している場合はバリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $otherBook = Book::factory()->create(['isbn' => '9876543210123']);
        $myBook = Book::factory()->create(['isbn' => '1111111111111']);

        $updateData = $this->validData($user, [
            'isbn' => $otherBook->isbn,
            'genres' => $genres->pluck('id')->toArray(),
        ]);

        // Act
        $validator = $this->validator($updateData, $myBook->id);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
    }
}
