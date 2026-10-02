<?php

namespace App\Services;

use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ThemeConfiguration
{
    public function selected(): ?string
    {
        // The preview can still boot before this additive migration is applied.
        return Schema::hasTable('theme_settings') ? ThemeSetting::find(1)?->theme : null;
    }

    public function save(User $user, string $theme): void
    {
        abort_unless($user->is_admin, 403);
        Validator::make(['theme' => $theme], ['theme' => ['required', Rule::in(['environment', ...array_keys(config('themes.themes'))])]])->validate();
        ThemeSetting::updateOrCreate(['id' => 1], ['theme' => $theme === 'environment' ? null : $theme]);
    }
}
