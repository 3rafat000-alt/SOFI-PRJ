<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixUserRoles extends Command
{
    protected $signature = 'user:fix-roles {--dry-run : List users without making changes}';
    protected $description = 'Remove admin role from non-admin users (users who should only be visitors)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fixed = 0;

        // Find users with admin role, excluding confirmed admin IDs
        $keepAdminIds = [1]; // Add real admin user IDs here
        $users = User::role('admin')
            ->whereNotIn('id', $keepAdminIds)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users with accidental admin role found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$users->count()} user(s) with admin role that may be accidental:");
        foreach ($users as $user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $this->line("  [{$user->id}] {$user->name} <{$user->email}> — roles: {$roles}");
        }

        if ($dryRun) {
            $this->info('Dry run — no changes made. Run without --dry-run to fix.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Remove admin role from these users?', false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $user->syncRoles('visitor');
            $this->line("  Fixed: [{$user->id}] {$user->name} → visitor only");
            $fixed++;
        }

        $this->info("Done. {$fixed} user(s) fixed.");
        return self::SUCCESS;
    }
}
