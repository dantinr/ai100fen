<?php

namespace App\Policies;

use App\Models\CourseRelation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CourseRelationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, CourseRelation $relation): bool
    {
        return Gate::forUser($user)->allows('view', $relation->course);
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, CourseRelation $relation): bool
    {
        return Gate::forUser($user)->allows('update', $relation->course);
    }

    public function delete(User $user, CourseRelation $relation): bool
    {
        return $this->update($user, $relation);
    }
}
