<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => $firstName . ' ' . $lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'student_id' => null,
            'global_role' => UserRole::Student,
            'is_active' => true,
            'last_login_at' => null,
            'preferences' => null,
            'current_team_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a student user.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'global_role' => UserRole::Student,
            'student_id' => 'STU' . fake()->unique()->numberBetween(100000, 999999),
        ]);
    }

    /**
     * Create a staff user.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'global_role' => UserRole::Staff,
            'student_id' => null,
        ]);
    }

    /**
     * Create a department admin user.
     */
    public function departmentAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'global_role' => UserRole::DepartmentAdmin,
            'student_id' => null,
        ]);
    }

    /**
     * Create a super admin user.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'global_role' => UserRole::SuperAdmin,
            'student_id' => null,
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a user with recent login.
     */
    public function withRecentLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create a user with custom preferences.
     */
    public function withPreferences(array $preferences = []): static
    {
        $defaultPreferences = [
            'theme' => fake()->randomElement(['light', 'dark']),
            'notifications' => fake()->boolean(),
            'timezone' => fake()->timezone(),
            'language' => fake()->randomElement(['en', 'es', 'fr']),
        ];

        return $this->state(fn (array $attributes) => [
            'preferences' => array_merge($defaultPreferences, $preferences),
        ]);
    }
}
