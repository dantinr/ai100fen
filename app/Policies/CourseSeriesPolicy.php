<?php

namespace App\Policies;

use App\Models\CourseSeries;
use App\Models\User;

class CourseSeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, CourseSeries $series): bool
    {
        return $user->is_admin === true && ! $series->trashed();
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, CourseSeries $series): bool
    {
        return $user->is_admin === true && ! $series->trashed();
    }

    public function delete(User $user, CourseSeries $series): bool
    {
        return $user->is_admin === true && ! $series->trashed();
    }

    public function restore(User $user, CourseSeries $series): bool
    {
        return $user->is_admin === true && $series->trashed();
    }

    public function forceDelete(User $user, CourseSeries $series): bool
    {
        return $user->is_admin === true && $series->trashed();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
