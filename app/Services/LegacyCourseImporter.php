<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Support\FrontendCatalog;
use App\Support\LegacyCourseDefinitions;
use Illuminate\Support\Facades\DB;

class LegacyCourseImporter
{
    public function run(bool $dryRun = false): array
    {
        $definitions = LegacyCourseDefinitions::courses(app(FrontendCatalog::class));

        return DB::transaction(function () use ($definitions, $dryRun) {
            $report = ['created' => 0, 'skipped' => 0, 'lessons' => 0, 'rows' => []];
            foreach ($definitions as $definition) {
                $existing = CourseSeries::withTrashed()->where('slug', $definition['slug'])->exists()
                    || DB::table('course_catalog_suppressions')->where('slug', $definition['slug'])->exists();
                $report['rows'][] = [$definition['slug'], $definition['category'], $existing ? '保留已有课程' : ($dryRun ? '计划新增草稿' : '新增草稿'), $existing ? 0 : count($definition['lessons'])];
                if ($existing) {
                    $report['skipped']++;

                    continue;
                }
                $report['created']++;
                $report['lessons'] += count($definition['lessons']);
                if ($dryRun) {
                    continue;
                }
                $lessons = $definition['lessons'];
                unset($definition['lessons']);
                $series = CourseSeries::create($definition);
                foreach ($lessons as $lesson) {
                    $series->lessons()->create($lesson);
                }
            }

            return $report;
        });
    }
}
