<?php

namespace App\Policies;

use App\Models\ScrapeRun;
use App\Models\User;

/**
 * Scrape history belongs to the data pipeline, which only administrators operate.
 */
class ScrapeRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ScrapeRun $run): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ScrapeRun $run): bool
    {
        return false;
    }

    public function delete(User $user, ScrapeRun $run): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
