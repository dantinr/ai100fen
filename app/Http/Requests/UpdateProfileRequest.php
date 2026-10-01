<?php

namespace App\Http\Requests;

use App\Support\AccountRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => is_string($this->name) ? trim($this->name) : $this->name]);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:50']];
    }

    public function messages(): array
    {
        return AccountRules::messages();
    }
}
