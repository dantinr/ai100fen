<?php

namespace App\Policies;

use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    public function viewAny(User $user): bool { return $user->is_admin === true; }
    public function view(User $user, LiveSession $session): bool { return $user->is_admin === true; }
    public function create(User $user): bool { return $user->is_admin === true; }
    public function update(User $user, LiveSession $session): bool { return $user->is_admin === true; }
    public function delete(User $user, LiveSession $session): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
}
