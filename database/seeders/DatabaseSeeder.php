<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The administrator is always created (idempotent). Change the password after first login.
        User::updateOrCreate(
            ['email' => mb_strtolower(env('ADMIN_EMAIL', 'admin@ttr.test'))],
            [
                'name' => 'TTR Administrator',
                'password' => env('ADMIN_PASSWORD', 'ChangeMe!2026'),
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Demo employers, jobs, candidates and leads: never in production.
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
