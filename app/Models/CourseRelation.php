<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRelation extends Model
{
    public const TYPES = ['prerequisite' => '前置课程', 'recommended' => '推荐课程', 'next' => '下一步课程'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseSeries::class, 'course_series_id');
    }

    public function relatedCourse(): BelongsTo
    {
        return $this->belongsTo(CourseSeries::class, 'related_course_series_id');
    }
}
