<?php

namespace App\Http\Requests;

use App\Support\AccountRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => AccountRules::password(),
        ];
    }

    public function messages(): array
    {
        return AccountRules::messages();
    }
}
