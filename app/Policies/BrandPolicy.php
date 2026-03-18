<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandPolicy
{
    use HandlesAuthorization;

    
 public function viewAny(User $user)
    {
        // Allow viewing the index for users with role_id 2 or 6
        return in_array($user->role_id, [2, 6]);
    }

    public function view(User $user, Brand $brand)
    {
        // Allow viewing individual brands for users with role_id 2 or 6
        return in_array($user->role_id, [2, 6]);
    }
    public function create(User $user)
    {
        // Allow creation for users with role_id 2 or 5
        return in_array($user->role_id, [2, 6]);
    }

    public function update(User $user, Brand $brand)
    {
        // Allow update if the user has role_id 2 or 5, and owns the brand
        return in_array($user->role_id, [2, 6]) && $user->id === $brand->user_id;
    }

    public function delete(User $user, Brand $brand)
    {
        // Allow deletion if the user has role_id 2 or 5, and owns the brand
        return in_array($user->role_id, [2, 6]) && $user->id === $brand->user_id;
    }
}