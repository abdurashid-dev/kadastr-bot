<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only CEOs and Registrators can view all users
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile, CEOs and Registrators can view any user
        return $user->id === $model->id || $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // CEOs and Registrators can create new users
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // CEOs and Registrators can update any user
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // CEOs and Registrators can delete users
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can assign roles.
     */
    public function assignRole(User $user, User $model): bool
    {
        // CEOs and Registrators can assign roles
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can view user statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isCeo() || $user->isRegistrator();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isCeo() || $user->isRegistrator();
    }
}
