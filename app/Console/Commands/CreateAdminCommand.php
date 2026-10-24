<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--name= : Display name}
        {--email= : Email address (must be unique)}
        {--password= : Password; plain argument is avoided, leave empty to be prompted}';

    protected $description = 'Create or reset an admin user (hashed, never logged)';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: text('Name', default: 'Varilevke Admin'));
        $email = (string) ($this->option('email') ?: text('Email', default: 'admin@varilevke.test'));
        $password = (string) ($this->option('password') ?: password('Password *'));

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'is_admin' => true],
        );

        $this->info("Admin ready: {$email}");

        return self::SUCCESS;
    }
}
