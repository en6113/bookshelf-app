<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReadingPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'target_date' => 'required|date|after_or_equal:today',
        ];

        if ($this->isMethod('post')) {
            $rules['book_id'] = 'required|integer|exists:books,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください',
            'target_date.required' => '期日を入力してください',
            'target_date.date' => '期日は年月日の形式で入力してください',
            'target_date.after_or_equal:today' => '期日は今日以降の日付を指定してください',
        ];
    }
}
