<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Lesson extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'position' => 1, 'score' => 10, 'points' => 10, 'minutes' => 10,
        'is_free' => false, 'status' => 'draft', 'intro' => '', 'goal' => '',
        'steps' => '[]', 'prompt' => '', 'resources' => '[]', 'checks' => '[]',
    ];

    protected function casts(): array
    {
        return ['is_free' => 'boolean', 'objectives' => 'array', 'steps' => 'array', 'checks' => 'array', 'resources' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $lesson) {
            if (! $lesson->series()->exists()) {
                throw ValidationException::withMessages(['course_series_id' => '所属课程不存在或已在回收站，请先恢复课程。']);
            }
            if ($lesson->video_url && (! filter_var($lesson->video_url, FILTER_VALIDATE_URL) || parse_url($lesson->video_url, PHP_URL_SCHEME) !== 'https')) {
                throw ValidationException::withMessages(['video_url' => '视频地址必须是有效的HTTPS链接。']);
            }
            if ($lesson->video_poster !== null && (! is_string($lesson->video_poster) || ! preg_match('~\Alesson-video-posters/[a-zA-Z0-9._-]+\.(?:jpe?g|png|webp)\z~i', $lesson->video_poster))) {
                throw ValidationException::withMessages(['video_poster' => '请选择上传的课时播放器封面。']);
            }
            if (! in_array($lesson->status, ['draft', 'published', 'archived'], true)) {
                throw ValidationException::withMessages(['status' => '请选择有效的课时状态。']);
            }
            if ($lesson->exists && $lesson->isDirty('course_series_id')) {
                throw ValidationException::withMessages(['course_series_id' => '已有课时不能移动到另一门课程。']);
            }
            if ($lesson->exists && $lesson->isDirty(['checks', 'points', 'score']) && $lesson->progress()->exists()) {
                throw ValidationException::withMessages(['checks' => '已有学习记录，不能直接改变验收项或分值。请创建新的课时草稿。']);
            }
            foreach (['checks', 'objectives'] as $field) {
                $items = $lesson->$field;
                if ($items !== null && (! is_array($items) || ! array_is_list($items) || collect($items)->contains(fn ($item) => ! is_string($item) || trim($item) === ''))) {
                    throw ValidationException::withMessages([$field => '请填写有效的目标或验收清单。']);
                }
            }
            foreach (['steps', 'resources'] as $field) {
                if (! is_array($lesson->$field) || ! array_is_list($lesson->$field)) {
                    throw ValidationException::withMessages([$field => '请填写有效的大纲或资料列表。']);
                }
            }
            foreach ($lesson->steps as $step) {
                if (! is_array($step) || ! is_string($step['title'] ?? null) || blank($step['title']) || ! is_string($step['body'] ?? null) || ($lesson->status === 'published' && blank($step['body']))) {
                    throw ValidationException::withMessages(['steps' => '每个步骤需要标题和正文。']);
                }
            }
            $resourceNames = [];
            foreach ($lesson->resources as $resource) {
                if (! is_array($resource) || ! is_string($resource['name'] ?? null) || ! preg_match('/\A[a-z0-9][a-z0-9._-]*\z/', $resource['name']) || in_array($resource['name'], $resourceNames, true) || ! is_string($resource['label'] ?? null) || blank($resource['label']) || ! is_string($resource['content'] ?? null)) {
                    throw ValidationException::withMessages(['resources' => '资料需唯一安全文件名、显示名称与文本内容。']);
                }
                $resourceNames[] = $resource['name'];
            }
            if ($lesson->status === 'published' && (blank($lesson->goal) || blank($lesson->prompt) || empty($lesson->steps) || empty($lesson->checks))) {
                throw ValidationException::withMessages(['lesson' => '发布前需补齐目标、步骤、Prompt 与验收标准。']);
            }
        });
        static::saved(function (self $lesson) {
            // Structural lesson changes require a course review and republish.
            if ($lesson->wasRecentlyCreated || $lesson->wasChanged(['status', 'points', 'score', 'position'])) {
                $lesson->series()->where('status', 'published')->update(['status' => 'draft']);
            }
        });
    }

    public function videoPosterUrl(): ?string
    {
        return is_string($this->video_poster) && preg_match('~\Alesson-video-posters/[a-zA-Z0-9._-]+\.(?:jpe?g|png|webp)\z~i', $this->video_poster)
            && Storage::disk('public')->exists($this->video_poster)
            ? Storage::disk('public')->url($this->video_poster)
            : null;
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(CourseSeries::class, 'course_series_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
