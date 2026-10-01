<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

class AccountRules
{
    public static function password(): array
    {
        return [...self::passwordInput(), 'confirmed', Password::min(8)];
    }

    public static function passwordInput(): array
    {
        return ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                $fail('密码过长或含有无效字符，请重新输入。');
            }
        }];
    }

    public static function messages(): array
    {
        return [
            'name.required' => '请输入昵称。',
            'name.string' => '请输入有效昵称。',
            'name.max' => '昵称最多50个字符。',
            'email.required' => '请输入邮箱。',
            'email.string' => '请输入有效的邮箱地址。',
            'email.email' => '请输入有效的邮箱地址。',
            'email.max' => '邮箱地址过长。',
            'email.unique' => '这个邮箱已经注册，请直接登录。',
            'password.required' => '请输入密码。',
            'password.string' => '请输入有效密码。',
            'password.max' => '密码过长，请重新输入。',
            'password.min' => '密码至少需要8个字符。',
            'password.confirmed' => '两次输入的密码不一致。',
            'current_password.required' => '请输入当前密码。',
            'current_password.current_password' => '当前密码不正确。',
            'remember.boolean' => '记住登录状态选项无效。',
        ];
    }
}
