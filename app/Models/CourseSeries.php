<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourseSeries extends Model
{
    protected $table = 'course_series';

    protected $guarded = ['id'];

    protected $attributes = [
        'user_intent' => '', 'final_outcome' => '', 'completion_criteria' => '[]',
        'agent_role' => '[]', 'human_judgment_required' => '[]', 'recommendation_keywords' => '[]',
        'minutes' => 100, 'price' => 100, 'is_free' => false, 'status' => 'draft', 'sort_order' => 1000,
    ];

    protected function casts(): array
    {
        return ['is_free' => 'boolean', 'price' => 'decimal:2', 'sort_order' => 'integer', 'objectives' => 'array', 'completion_criteria' => 'array', 'agent_role' => 'array', 'human_judgment_required' => 'array', 'recommendation_keywords' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $series) {
            Validator::make(['sort_order' => $series->getAttributes()['sort_order'] ?? null], [
                'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            ])->validate();
            if (! in_array($series->status, ['draft', 'published', 'archived'], true)) {
                throw ValidationException::withMessages(['status' => '请选择有效的课程状态。']);
            }
            foreach (['completion_criteria', 'agent_role', 'human_judgment_required', 'recommendation_keywords', 'objectives'] as $field) {
                $items = $series->$field;
                if ($items !== null && (! is_array($items) || ! array_is_list($items) || collect($items)->contains(fn ($item) => ! is_string($item) || trim($item) === ''))) {
                    throw ValidationException::withMessages([$field => '请填写有效的具体事项清单。']);
                }
            }
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
            if ((int) $series->lessons()->where('status', 'published')->reorder()->orderByDesc('position')->orderByDesc('id')->first()->score !== 100) {
                throw ValidationException::withMessages(['status' => '最后一个已发布课时须为100分，并按课程完成标准验收整个任务。']);
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

    public function scopeDisplayOrder(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
