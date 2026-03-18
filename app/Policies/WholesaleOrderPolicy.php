<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\WholesaleOrder;
use App\Models\User;

class WholesaleOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->organization_id !== null;
    }
    
    public function view(User $user, WholesaleOrder $order)
    {
        return $user->organization_id === $order->organization_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WholesaleOrder  $wholesaleOrder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, WholesaleOrder $wholesaleOrder)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WholesaleOrder  $wholesaleOrder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, WholesaleOrder $wholesaleOrder)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WholesaleOrder  $wholesaleOrder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, WholesaleOrder $wholesaleOrder)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\WholesaleOrder  $wholesaleOrder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, WholesaleOrder $wholesaleOrder)
    {
        //
    }
}
