<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourseSeries extends Model
{
    use SoftDeletes;

    protected $table = 'course_series';

    protected $guarded = ['id', 'deleted_at'];

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
        static::created(fn (self $series) => DB::table('course_catalog_suppressions')->where('slug', $series->slug)->delete());
        static::saving(function (self $series) {
            if ($series->exists && $series->trashed() && ! $series->isDirty('deleted_at')) {
                throw ValidationException::withMessages(['status' => '请先从回收站恢复课程，再编辑内容。']);
            }
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
                throw ValidationException::withMessages(['lessons' => '发布前至少需要一个已发布课时。']);
            }
        });
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function courseRelations(): HasMany
    {
        return $this->hasMany(CourseRelation::class)->orderBy('sort_order')->orderBy('id');
    }

    public function coverUrl(): ?string
    {
        return is_string($this->cover) && preg_match('~\Acourse-covers/[a-zA-Z0-9._-]+\.(?:jpe?g|png|webp)\z~i', $this->cover)
            && Storage::disk('public')->exists($this->cover)
            ? Storage::disk('public')->url($this->cover)
            : null;
    }

    public function scopeFreeLab(Builder $query): void
    {
        $query->where('status', 'published')->where('is_free', true)
            ->whereDoesntHave('lessons', fn (Builder $lessons) => $lessons->whereNotIn('status', ['published', 'archived']))
            ->whereHas('lessons', fn (Builder $lessons) => $lessons->where('status', 'published'))
            // Gradual publication is allowed; Free Lab still offers complete tasks.
            ->where(Lesson::selectRaw('SUM(points)')->whereColumn('course_series_id', 'course_series.id')->where('status', '!=', 'archived'), 100)
            ->where(Lesson::select('score')->whereColumn('course_series_id', 'course_series.id')
                ->where('status', 'published')
                ->orderByDesc('position')->orderByDesc('id')->limit(1), 100);
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
