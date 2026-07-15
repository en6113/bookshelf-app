<?php

namespace Tests\Unit\Api\V1;

use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreBookRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2026/05/30',
            'description' => 'テスト書籍の説明',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=100',
        ], $overrides);
    }

    /** @test */
    public function 正しい入力項目でバリデーションを通過する(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $validator = $this->validator($this->validData($user, [
            'genres' => $genres->pluck('id')->toArray(),
        ]));

        $this->assertTrue($validator->passes());
    }

    /** @test
     *  @dataProvider requiredFieldProvider
     */
    public function 必須項目が空の時にバリデーションエラーになる(array $invalidData, string $errorKey): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $baseData = $this->validData($user, [
            'genres' => $genres->pluck('id')->toArray(),
        ]);
        $testData = array_merge($baseData, $invalidData);

        // Act
        $validator = $this->validator($testData);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey($errorKey, $validator->errors()->toArray());
    }

    /**
     * 必須項目のテストデータを供給するプロバイダ
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'user_idが空の時' => [['user_id' => ''], 'user_id'],
            'titleが空の時' => [['title' => ''], 'title'],
            'authorが空の時' => [['author' => ''], 'author'],
            'genresが空の時' => [['genres' => []], 'genres'],
        ];
    }

    /** @test */
    public function isbnが既に存在している時にバリデーションエラーになる(): void
    {
        // Arrange
        $existingBook = Book::factory()->create(['isbn' => '1234567890123']);

        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $storeData = $this->validData($user, [
            'isbn' => $existingBook->isbn,
            'genres' => $genres->pluck('id')->toArray(),
        ]);

        // Act
        $validator = $this->validator($storeData);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
    }

    /** @test
     *  @dataProvider lengthProvider
     */
    public function 文字数超過または不足時にバリデーションエラーになる(array $lengthData, string $errorKey): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $baseData = $this->validData($user, [
            'genres' => $genres->pluck('id')->toArray(),
        ]);
        $testData = array_merge($baseData, $lengthData);

        // Act
        $validator = $this->validator($testData);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey($errorKey, $validator->errors()->toArray());
    }

    /**
     * 文字数制限があるテストデータを供給するプロバイダ
     */
    public static function lengthProvider(): array
    {
        return [
            'titleが256文字の時' => [['title' => str_repeat('a', 256)], 'title'],
            'authorが101文字の時' => [['author' => str_repeat('a', 101)], 'author'],
            'isbnが9文字の時（文字数不足）' => [['isbn' => str_repeat('1', 9)], 'isbn'],
            'isbnが14文字の時（文字数超過）' => [['isbn' => str_repeat('1', 14)], 'isbn'],
            'descriptionが256文字の時' => [['description' => str_repeat('a', 256)], 'description'],
            'image_urlが256文字の時' => [['image_url' => str_repeat('a', 256)], 'image_url'],
        ];
    }

    /** @test
     *  @dataProvider formatProvider
     */
    public function フォーマット不正時にバリデーションエラーになる(array $formatData, string $errorKey): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $baseData = $this->validData($user, [
            'genres' => $genres->pluck('id')->toArray(),
        ]);
        $testData = array_merge($baseData, $formatData);

        // Act
        $validator = $this->validator($testData);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey($errorKey, $validator->errors()->toArray());
    }

    /**
     * フォーマット形式があるテストデータを供給するプロバイダ
     */
    public static function formatProvider(): array
    {
        return [
            'published_dateのフォーマットが年だけの時' => [['published_date' => '2026'], 'published_date'],
            'published_dateのフォーマットが年月だけの時' => [['published_date' => '2026/05'], 'published_date'],
            'published_dateのフォーマットが日付形式でない時' => [['published_date' => '12345678'], 'published_date'],
            'published_dateのフォーマットが存在しない日付の時' => [['published_date' => '2026/02/30'], 'published_date'],
            'image_urlのフォーマットがURL形式でない時' => [['image_url' => 'not-a-url'], 'image_url'],
        ];
    }
}
