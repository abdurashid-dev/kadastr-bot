<?php

namespace App\Policies;

use App\Models\UploadedFile;
use App\Models\User;

class UploadedFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['checker', 'registrator', 'ceo']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UploadedFile $uploadedFile): bool
    {
        // Users can view their own files
        if ($uploadedFile->user_id === $user->id) {
            return true;
        }

        // Checkers, registrators, and CEOs can view all files
        return $user->hasAnyRole(['checker', 'registrator', 'ceo']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only regular users can upload files (through Telegram)
        return $user->hasRole('user');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UploadedFile $uploadedFile): bool
    {
        // Only checkers and registrators can update file status
        return $user->hasAnyRole(['checker', 'registrator']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UploadedFile $uploadedFile): bool
    {
        // Only admins can delete files (not implemented in this workflow)
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UploadedFile $uploadedFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UploadedFile $uploadedFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can approve files as a checker.
     */
    public function approveAsChecker(User $user, UploadedFile $uploadedFile): bool
    {
        return $user->isChecker() && $uploadedFile->canBeApprovedByChecker();
    }

    /**
     * Determine whether the user can approve files as a registrator.
     */
    public function approveAsRegistrator(User $user, UploadedFile $uploadedFile): bool
    {
        return $user->isRegistrator() && $uploadedFile->canBeApprovedByRegistrator();
    }

    /**
     * Determine whether the user can reject files.
     */
    public function reject(User $user, UploadedFile $uploadedFile): bool
    {
        if ($user->isChecker()) {
            return $uploadedFile->canBeApprovedByChecker();
        }

        if ($user->isRegistrator()) {
            return $uploadedFile->canBeApprovedByRegistrator();
        }

        return false;
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->isCeo();
    }
}
