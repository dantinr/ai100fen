<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->is_admin === true;
    }

    public function reorder(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
