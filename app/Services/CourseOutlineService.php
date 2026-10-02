<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourseOutlineService
{
    public function reorder(User $user, CourseSeries $series, array $order): void
    {
        Gate::forUser($user)->authorize('update', $series);
        Validator::make(['order' => $order], ['order' => ['array', 'list'], 'order.*' => ['integer', 'min:1', 'distinct']])->validate();
        DB::transaction(function () use ($series, $order) {
            $series = CourseSeries::whereKey($series->id)->lockForUpdate()->firstOrFail();
            $lessons = $series->lessons()->lockForUpdate()->get()->keyBy('id');
            $ids = array_map('strval', $lessons->keys()->all());
            if (! array_is_list($order) || count($order) !== count($ids) || count(array_unique($order)) !== count($order) || array_diff(array_map('strval', $order), $ids)) {
                throw ValidationException::withMessages(['order' => '请显示本课程全部课时后再排序，不允许混入其他课程。']);
            }
            foreach ($order as $position => $id) {
                $lessons[$id]->update(['position' => $position + 1]);
            }
        });
    }
}
