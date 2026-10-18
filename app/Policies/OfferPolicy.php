<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

/**
 * Operators prepare and send offers; only administrators may delete them.
 */
class OfferPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Offer $offer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Offer $offer): bool
    {
        return true;
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Offer $offer): bool
    {
        return $user->isAdmin();
    }
}
