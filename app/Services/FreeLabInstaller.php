<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Support\FreeLabContent;
use Illuminate\Support\Facades\DB;

class FreeLabInstaller
{
    public function install(): int
    {
        return DB::transaction(function () {
            $created = 0;
            foreach (FreeLabContent::courses() as $definition) {
                // Never overwrite edited courses or any existing business records.
                if (CourseSeries::withTrashed()->where('slug', $definition['slug'])->exists()
                    || DB::table('course_catalog_suppressions')->where('slug', $definition['slug'])->exists()) {
                    continue;
                }
                $lesson = $definition['lesson'];
                unset($definition['lesson']);
                $series = CourseSeries::create($definition + ['status' => 'draft', 'is_free' => true, 'price' => '0.00']);
                $series->lessons()->create($lesson + ['status' => 'published', 'is_free' => false, 'minutes' => $definition['minutes'], 'score' => 100, 'points' => 100, 'checks' => $definition['completion_criteria']]);
                $series->update(['status' => 'published']);
                $created++;
            }

            return $created;
        });
    }
}
