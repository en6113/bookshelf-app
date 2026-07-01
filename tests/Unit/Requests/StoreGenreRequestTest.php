<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreGenreRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreGenreRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function 正しい入力値でバリデーションエラーを通過する(): void
    {
        $genre = ['name' => 'ジャンル名'];

        $validator = $this->validator($genre);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function ジャンル名が既に存在する場合はバリデーションエラーになる(): void
    {
        $existingGenre = Genre::factory()->create(['name' => '存在するジャンル名']);

        $genre = ['name' => $existingGenre->name];

        $validator = $this->validator($genre);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function ジャンル名が51文字の時はバリデーションエラーになる(): void
    {
        $genre = ['name' => str_repeat('a', 51)];

        $validator = $this->validator($genre);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}
