<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantPersonalTransactionView extends Command
{
    protected $signature = 'admin:grant-personal-view {userId : The admin user ID} {--revoke : Revoke permission instead of grant}';
    protected $description = 'Grant or revoke the ability for an admin to include personal transactions in admin views.';

    public function handle(): int
    {
        $userId = (int) $this->argument('userId');
        $revoke = (bool) $this->option('revoke');

        $user = User::find($userId);
        if (! $user) {
            $this->error('User not found');
            return self::FAILURE;
        }

        if ($user->role !== 'admin') {
            $this->warn('User is not an admin. Updating flag anyway.');
        }

        $user->can_view_personal_transactions = ! $revoke;
        $user->save();

        $this->info(sprintf('%s permission %s for user #%d (%s).',
            'view_personal_transactions', $revoke ? 'revoked' : 'granted', $user->id, $user->name
        ));

        return self::SUCCESS;
    }
}
