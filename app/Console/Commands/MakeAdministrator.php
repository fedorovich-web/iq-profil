<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MakeAdministrator extends Command
{
    protected $signature = 'iq:make-admin {email}';

    protected $description = 'Grant administrator role to an existing registered user (CLI only).';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User not found; register the account first.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user): void {
            $user->forceFill(['is_admin' => true])->save();
            DB::table('admin_audit_logs')->insert([
                'admin_id' => $user->id,
                'action' => 'granted_administrator_role_by_cli',
                'target_type' => 'user',
                'target_id' => $user->id,
                'created_at' => now(),
            ]);
        });
        $this->info('Administrator role granted to user ID '.$user->id);

        return self::SUCCESS;
    }
}
