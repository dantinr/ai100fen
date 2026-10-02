<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdministrator extends Command
{
    protected $signature = 'admin:set {email : Existing account email} {--revoke : Remove admin access}';

    protected $description = 'Explicitly grant or revoke access to the Galaxy administration panel';

    public function handle(): int
    {
        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Account not found. Register an account first.');

            return self::FAILURE;
        }
        $grant = ! $this->option('revoke');
        if (! $this->confirm($grant ? 'Grant this account administrator access?' : 'Revoke administrator access?', false)) {
            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => $grant])->save();
        $this->info($grant ? 'Administrator access granted.' : 'Administrator access revoked.');

        return self::SUCCESS;
    }
}
