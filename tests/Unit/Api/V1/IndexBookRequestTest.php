<?php

namespace Tests\Unit\Api\V1;

use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexBookRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data): \Illuminate\Validation\Validator
    {
        $request = new IndexBookRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function キーワード・ジャンル・並び順・per_pageフィルタが有効である(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        // Act
        $validator = $this->validator([
            'keyword' => '夏目',
            'genre_id' => $genre->id,
            'sort' => 'oldest',
            'per_page' => 5,
        ]);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 検索フィルタをかけていない場合もバリデーションを通過できる(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->passes());
    }

    /** @test
     *  @dataProvider wrongProvider
     */
    public function 不正な値が入力された場合はバリデーションエラーになる(array $wrongData, string $errorkey): void
    {
        $genre = Genre::factory()->create();

        $validator = $this->validator([]);

        $this->assertTrue($validator->passes());
    }

    /**
     * 不正な値データを供給するプロバイダ
     */
    public static function wrongProvider(): array
    {
        return [
            'キーワードが256文字の時' => [['keyword' => str_repeat('a', 256)], 'keyword'],
            'genre_idが存在しない時' => [['genre_id' => 999], 'genre'],
            '並び順が存在しない時' => [['sort' => 'not-sort'], 'sort'],
            'per_pageが0の時' => [['per_page' => 0], 'per_page'],
            'per_pageが101の時（超過）' => [['per_page' => 101], 'per_page'],
        ];
    }
}
