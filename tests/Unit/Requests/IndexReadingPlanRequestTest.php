<?php

namespace Tests\Unit\Requests;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexReadingPlanRequestTest extends TestCase
{
    private function validator(array $data): \Illuminate\Validation\Validator
    {
        $request = new IndexReadingPlanRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function 有効な状態の場合はバリデーションを通過する(): void
    {
        $validator = $this->validator([
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * @test
     *
     * @dataProvider provideNullableData
     */
    public function 未指定や空の値の場合もバリデーションを通過できる(array $data): void
    {
        $validator = $this->validator($data);

        $this->assertTrue($validator->passes());
    }

    public static function provideNullableData(): array
    {
        return [
            'リクエスト自体がない' => [[]],
            '値がnull' => [['status' => null]],
            '値が空文字' => [['status' => '']],
        ];
    }

    /** @test */
    public function 存在しない状態が入力された場合はバリデーションエラーになり、カスタムメッセージを返す(): void
    {
        $validator = $this->validator([
            'status' => 'invalid-status',
        ]);

        $this->assertFalse($validator->passes());

        $this->assertSame(
            '選択肢から選択してください',
            $validator->errors()->first('status')
        );
    }
}
