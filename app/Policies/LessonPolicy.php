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
        return $user->is_admin === true && ! $lesson->trashed() && $lesson->series()->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->is_admin === true && ! $lesson->trashed() && $lesson->series()->exists();
    }

    public function reorder(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson) && ! $lesson->progress()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function restore(User $user, Lesson $lesson): bool
    {
        return $user->is_admin === true && $lesson->trashed() && $lesson->series()->exists();
    }

    public function restoreAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function forceDelete(User $user, Lesson $lesson): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
