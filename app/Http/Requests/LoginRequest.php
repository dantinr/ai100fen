<?php

namespace App\Http\Requests;

use App\Support\AccountRules;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => AccountRules::passwordInput(),
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return AccountRules::messages();
    }

    public function authenticate(): void
    {
        $key = hash('sha256', $this->string('email').'|'.$this->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($this));
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "尝试次数过多，请在{$seconds}秒后重试。"]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => '邮箱或密码不正确。']);
        }

        RateLimiter::clear($key);
    }
}
