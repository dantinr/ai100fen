<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveLessonNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'notes' => ['present', 'nullable', 'string', 'max:10000'],
            'notes_version' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    public function messages(): array
    {
        return ['notes.max' => '课堂笔记最多10000字。', 'notes_version.required' => '请刷新页面后重试。'];
    }
}
