<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserInvitation>
 */
class UserInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(64),
            'team_id' => Team::factory(),
            'role' => fake()->randomElement([TeamRole::Member, TeamRole::Staff, TeamRole::Admin]),
            'invited_by' => User::factory()->departmentAdmin(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'accepted_by' => null,
        ];
    }

    /**
     * Create an expired invitation.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Create an accepted invitation.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'accepted_by' => User::factory(),
        ]);
    }

    /**
     * Create a staff invitation.
     */
    public function forStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TeamRole::Staff,
        ]);
    }

    /**
     * Create an admin invitation.
     */
    public function forAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TeamRole::Admin,
        ]);
    }

    /**
     * Create a member invitation.
     */
    public function forMember(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TeamRole::Member,
        ]);
    }

    /**
     * Create an invitation expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Create a long-term invitation.
     */
    public function longTerm(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(fake()->numberBetween(14, 30)),
        ]);
    }
}
