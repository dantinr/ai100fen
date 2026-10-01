<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CourseSeries extends Model
{
    protected $table = 'course_series';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_free' => 'boolean', 'price' => 'decimal:2', 'completion_criteria' => 'array', 'agent_role' => 'array', 'human_judgment_required' => 'array', 'recommendation_keywords' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $series) {
            if (! in_array($series->category, ['solve', 'create', 'explore'], true)) {
                throw ValidationException::withMessages(['category' => '请选择唯一的 Solve / Create / Explore 类别。']);
            }
            if ($series->status !== 'published') {
                return;
            }
            foreach (['title', 'user_intent', 'final_outcome'] as $field) {
                if (! is_string($series->$field) || trim($series->$field) === '') {
                    throw ValidationException::withMessages([$field => '发布前必须填写完整课程定义。']);
                }
            }
            foreach (['completion_criteria', 'agent_role', 'human_judgment_required'] as $field) {
                $items = $series->$field;
                if (! is_array($items) || $items === [] || collect($items)->contains(fn ($item) => ! is_string($item) || trim($item) === '')) {
                    throw ValidationException::withMessages([$field => '请填写非空的具体事项清单。']);
                }
            }
            if (! $series->exists || ! $series->lessons()->where('status', 'published')->exists()) {
                throw ValidationException::withMessages(['lessons' => '发布前至少需要一个完整的 Lesson。']);
            }
            if ($series->is_free && ($series->lessons()->where('status', '!=', 'published')->exists() || (int) $series->lessons()->sum('points') !== 100)) {
                throw ValidationException::withMessages(['lessons' => '完整免费任务的全部步骤须已发布，验收权重合计100分。']);
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function scopeFreeLab(Builder $query): void
    {
        $query->where('status', 'published')->where('is_free', true)
            ->whereDoesntHave('lessons', fn (Builder $lessons) => $lessons->where('status', '!=', 'published'))
            ->whereHas('lessons', fn (Builder $lessons) => $lessons->where('status', 'published'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
