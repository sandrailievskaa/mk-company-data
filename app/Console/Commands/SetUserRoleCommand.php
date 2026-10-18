<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRoleCommand extends Command
{
    protected $signature = 'app:set-user-role {email : E-mail of an existing user} {role : admin or operator}';

    protected $description = 'Change the role of a panel user';

    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->argument('role'));
        if (! $role) {
            $this->error('Role must be one of: '.implode(', ', array_map(fn (UserRole $r) => $r->value, UserRole::cases())));

            return Command::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User not found.');

            return Command::FAILURE;
        }

        if ($user->isAdmin() && $role !== UserRole::Admin && User::query()->where('role', UserRole::Admin->value)->count() === 1) {
            $this->error('Refusing to demote the last administrator.');

            return Command::FAILURE;
        }

        $user->update(['role' => $role]);
        $this->info("{$user->email} is now {$role->getLabel()}.");

        return Command::SUCCESS;
    }
}
