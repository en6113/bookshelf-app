<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:100',
            'isbn' => [
                'nullable',
                'string',
                'min:10',
                'max:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255|url',
            'genres' => 'required|array',
            'genres.*' => 'integer|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者を入力してください',
            'author.max' => '著者名は100文字以内で入力してください',
            'isbn.required' => 'ISBNを入力してください',
            'isbn.min' => ' ISBNは10桁以上で入力してください',
            'isbn.max' => ' ISBNは13桁以内で入力してください',
            'isbn.unique' => '該当のISBN(書籍)は既に存在しています',
            'published_date.date' => '出版日の形式が正しくありません。YYYY/MM/DD の形式で入力してください',
            'description.max' => '説明は255文字以内で入力してください',
            'image_url.url' => '画像URLはURL形式で入力してください',
            'genres.required' => 'ジャンルを1つ以上選択してください',
        ];
    }
}
