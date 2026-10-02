<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourseDisplayReorderService
{
    public function reorder(User $user, array $order): void
    {
        Gate::forUser($user)->authorize('viewAny', CourseSeries::class);
        Validator::make(['order' => $order], [
            'order' => ['array', 'list'],
            'order.*' => ['integer', 'min:1', 'distinct'],
        ])->validate();

        DB::transaction(function () use ($user, $order): void {
            $courses = CourseSeries::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('strval', $courses->keys()->all());

            if (count($order) !== count($ids) || count($order) > 999999 ||
                array_diff(array_map('strval', $order), $ids) || array_diff($ids, array_map('strval', $order))) {
                throw ValidationException::withMessages(['order' => '请清除搜索和筛选，显示全部课程后再拖动排序。']);
            }

            foreach ($courses as $course) {
                Gate::forUser($user)->authorize('update', $course);
            }
            foreach ($order as $position => $id) {
                if ($courses[$id]->sort_order !== $position + 1) {
                    $courses[$id]->update(['sort_order' => $position + 1]);
                }
            }
        });
    }
}
