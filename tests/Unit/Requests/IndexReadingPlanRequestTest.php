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

    /**
     * @test
     *
     * @dataProvider provideStatusData
     *  */
    public function 状態フィルタが有効である(array $data): void
    {
        $validator = $this->validator($data);

        $this->assertTrue($validator->passes());
    }

    public static function provideStatusData(): array
    {
        return [
            '進行中' => [['status' => ReadingPlanStatus::InProgress->value]],
            '完了' => [['status' => ReadingPlanStatus::Completed->value]],
            '期限切れ' => [['status' => ReadingPlanStatus::Expired->value]],
        ];
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
