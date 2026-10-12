<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the staff list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageStaff);
    }

    /**
     * Determine whether the user can create staff accounts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageStaff);
    }

    /**
     * Determine whether the user can update the staff account.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageStaff);
    }

    /**
     * Determine whether the user can deactivate the staff account.
     */
    public function deactivate(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageStaff)
            && $model->is_active
            && ! $user->is($model);
    }

    /**
     * Determine whether the user can reactivate the staff account.
     */
    public function activate(User $user, User $model): bool
    {
        return $user->hasPermission(Permission::ManageStaff)
            && ! $model->is_active;
    }
}
