<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ReviewRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReviewRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data): \Illuminate\Validation\Validator
    {
        $request = new ReviewRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function 正しい入力値でバリデーションを通過する(): void
    {
        $review = [
            'rating' => 5,
            'comment' => 'テストのコメント',
        ];

        $validator = $this->validator($review);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 評価が未入力だとバリデーションエラーになる(): void
    {
        $review = [
            'rating' => '',
            'comment' => 'テストのコメント',
        ];

        $validator = $this->validator($review);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
    }

    /** @test */
    public function コメントが未入力だとバリデーションエラーになる(): void
    {
        $review = [
            'rating' => 5,
            'comment' => '',
        ];

        $validator = $this->validator($review);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('comment', $validator->errors()->toArray());
    }

    /** @test */
    public function 評価が0の場合はバリデーションエラーになる(): void
    {
        $review = [
            'rating' => 0,
            'comment' => 'テストのコメント',
        ];

        $validator = $this->validator($review);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
    }

    /** @test */
    public function 評価が規定値以外の場合はバリデーションエラーになる(): void
    {
        $review = [
            'rating' => 6,
            'comment' => 'テストのコメント',
        ];

        $validator = $this->validator($review);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
    }

    /** @test */
    public function コメントが256文字の時バリデーションエラーになる(): void
    {
        $review = [
            'rating' => 5,
            'comment' => str_repeat('あ', 256),
        ];

        $validator = $this->validator($review);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('comment', $validator->errors()->toArray());
    }
}
