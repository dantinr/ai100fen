<?php

namespace App\Filament\Support;

use App\Models\CourseSeries;
use App\Services\CourseRelationService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

trait ManagesCourseFormRelations
{
    #[Locked]
    public array $courseRelationSnapshots = [];

    protected function courseRelationFormData(CourseSeries $record): array
    {
        $data = [];
        foreach (['prerequisite_courses' => 'prerequisite', 'next_courses' => 'next'] as $field => $type) {
            $data[$field] = $record->courseRelations()->where('relation_type', $type)
                ->get(['id', 'related_course_series_id', 'sort_order', 'description'])->toArray();
            $this->courseRelationSnapshots[$type] = app(CourseRelationService::class)->snapshot($record, $type);
        }

        return $data;
    }

    protected function persistCourseForm(array $data, callable $save): Model
    {
        return $this->persistContent(function () use ($data, $save) {
            return DB::transaction(function () use ($data, $save) {
                // Use the graph lock before course updates and relationship writes.
                CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->first();
                $relations = [];
                foreach (['prerequisite_courses' => 'prerequisite', 'next_courses' => 'next'] as $field => $type) {
                    $relations[$field] = $data[$field] ?? [];
                    unset($data[$field]);
                }
                $record = $save($data);
                foreach (['prerequisite_courses' => 'prerequisite', 'next_courses' => 'next'] as $field => $type) {
                    try {
                        app(CourseRelationService::class)->sync(Filament::auth()->user(), $record, $type,
                            $relations[$field], $this->courseRelationSnapshots[$type] ?? hash('sha256', '[]'));
                    } catch (ValidationException $exception) {
                        $errors = [];
                        foreach ($exception->errors() as $path => $messages) {
                            $path = preg_replace('/\Arows\.?/', '', $path);
                            $errors[$field.($path === '' ? '' : '.'.$path)] = $messages;
                        }
                        throw ValidationException::withMessages($errors);
                    }
                }

                return $record;
            }, 3);
        });
    }
}
