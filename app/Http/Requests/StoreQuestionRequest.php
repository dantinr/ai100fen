<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['title', 'goal', 'scope', 'outcome'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'submission_key' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', Rule::in(['solve', 'create', 'explore'])],
            'goal' => ['required', 'string', 'max:1500'],
            'scope' => ['nullable', 'string', 'max:1500'],
            'outcome' => ['required', 'string', 'max:1500'],
            'completion_criteria' => ['required', 'array', 'list', 'min:1', 'max:8'],
            'completion_criteria.*' => ['required', 'string', 'max:300'],
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => '任务名称', 'category' => '任务类别', 'goal' => '目标', 'scope' => '范围与限制',
            'outcome' => '期望产出', 'completion_criteria' => '验收标准', 'completion_criteria.*' => '验收标准', 'confirmed' => '私人保存确认'];
    }
}
