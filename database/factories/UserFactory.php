<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'Password123',
            'role' => Role::Candidate,
            'phone' => '024'.fake()->numerify('#######'),
            'is_active' => true,
            'remember_token' => str()->random(10),
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function admin(): static
    {
        return $this->role(Role::Admin);
    }

    public function employer(): static
    {
        return $this->role(Role::Employer);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
