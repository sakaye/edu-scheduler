<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
            'Computer Science',
            'Mathematics',
            'Biology',
            'Chemistry',
            'Physics',
            'English Literature',
            'History',
            'Psychology',
            'Economics',
            'Political Science',
            'Art',
            'Music',
            'Philosophy',
            'Sociology',
            'Engineering',
            'Business Administration',
            'Education',
            'Nursing',
            'Medicine',
            'Law',
        ];

        $departmentCodes = [
            'CS', 'MATH', 'BIO', 'CHEM', 'PHYS',
            'ENG', 'HIST', 'PSYC', 'ECON', 'POLS',
            'ART', 'MUS', 'PHIL', 'SOC', 'ENGR',
            'BUS', 'EDU', 'NURS', 'MED', 'LAW',
        ];

        $name = fake()->randomElement($departments);
        $slug = Str::slug($name);
        $departmentCode = fake()->randomElement($departmentCodes);

        return [
            'name' => $name,
            'slug' => $slug,
            'department_code' => $departmentCode,
            'description' => fake()->paragraph(2),
            'contact_email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
            'settings' => null,
        ];
    }

    /**
     * Create an inactive team.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a team with custom settings.
     */
    public function withSettings(array $settings = []): static
    {
        $defaultSettings = [
            'max_students' => fake()->numberBetween(50, 1000),
            'auto_approve' => fake()->boolean(30), // 30% chance of auto-approval
            'notification_emails' => [
                fake()->safeEmail(),
                fake()->safeEmail(),
            ],
            'business_hours' => [
                'start' => '08:00',
                'end' => '17:00',
            ],
            'allow_student_registration' => fake()->boolean(80), // 80% allow
            'require_approval' => fake()->boolean(70), // 70% require approval
        ];

        return $this->state(fn (array $attributes) => [
            'settings' => array_merge($defaultSettings, $settings),
        ]);
    }

    /**
     * Create a computer science department.
     */
    public function computerScience(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'department_code' => 'CS',
            'description' => 'The Department of Computer Science offers comprehensive programs in software engineering, algorithms, and computer systems.',
            'contact_email' => 'cs@university.edu',
        ]);
    }

    /**
     * Create a mathematics department.
     */
    public function mathematics(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Mathematics',
            'slug' => 'mathematics',
            'department_code' => 'MATH',
            'description' => 'The Mathematics Department provides rigorous training in pure and applied mathematics.',
            'contact_email' => 'math@university.edu',
        ]);
    }

    /**
     * Create a biology department.
     */
    public function biology(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Biology',
            'slug' => 'biology',
            'department_code' => 'BIO',
            'description' => 'The Biology Department focuses on molecular biology, ecology, and life sciences research.',
            'contact_email' => 'biology@university.edu',
        ]);
    }
}
