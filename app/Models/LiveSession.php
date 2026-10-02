<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LiveSession extends Model
{
    protected $guarded = ['id'];

    protected $attributes = ['access_type' => 'public', 'status' => 'draft'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $session) => $session->slug ??= (string) Str::uuid());

        static::saving(function (self $session) {
            if (! in_array($session->status, ['draft', 'scheduled', 'live', 'processing', 'replay', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => '请选择有效的直播状态。']);
            }
            if ($session->access_type !== 'public') {
                throw ValidationException::withMessages(['access_type' => '当前只支持公开直播。']);
            }
            if (! $session->starts_at || ! $session->ends_at || $session->ends_at <= $session->starts_at) {
                throw ValidationException::withMessages(['ends_at' => '结束时间必须晚于开始时间。']);
            }
            foreach (['meeting_url', 'replay_url'] as $field) {
                if ($session->$field && (! filter_var($session->$field, FILTER_VALIDATE_URL) || parse_url($session->$field, PHP_URL_SCHEME) !== 'https')) {
                    throw ValidationException::withMessages([$field => '链接必须是有效的HTTPS地址。']);
                }
            }
            if ($session->status === 'live' && blank($session->meeting_url)) {
                throw ValidationException::withMessages(['meeting_url' => '直播中需要填写课堂入口。']);
            }
            if ($session->status === 'replay' && blank($session->replay_url)) {
                throw ValidationException::withMessages(['replay_url' => '开放回放前需要填写回放链接。']);
            }
        });
    }
}
