<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ReadingPlanRequest;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReadingPlanRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data, string $method = 'POST'): \Illuminate\Validation\Validator
    {
        $request = ReadingPlanRequest::create('/reading-plans', $method, $data);

        $request->setContainer(app());
        $request->setRedirector(app(Redirector::class));

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function store時に正しい入力項目でバリデーションを通過する(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator([
            'book_id' => $book->id,
            'target_date' => '2026/07/09',
        ], 'POST');

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function store時に書籍が空だとバリデーションエラーになる(): void
    {
        $validator = $this->validator([
            'target_date' => '2026/07/09',
        ], 'POST');

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('book_id'));
    }

    /** @test */
    public function update時は書籍が空でもバリデーションを通過する(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator([
            'target_date' => '2026/07/09',
        ], 'PUT');

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 期日が空だとバリデーションエラーになる(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator([
            'book_id' => $book->id,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('target_date'));
    }

    /** @test */
    public function 期日の形式が不正だとバリデーションエラーになる(): void
    {
        $book = Book::factory()->create();

        $validator = $this->validator([
            'book_id' => $book->id,
            'target_date' => 'non-date',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('target_date'));
    }
}
