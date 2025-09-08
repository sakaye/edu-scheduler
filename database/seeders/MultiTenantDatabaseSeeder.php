<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\TeamRole;
use App\Enums\MembershipStatus;
use App\Models\Team;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MultiTenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedSuperAdmin();
            $this->seedDefaultTeams();
            $this->seedDemoUsers();
            $this->seedTeamMemberships();
            $this->seedInvitations();
        });
    }

    /**
     * Create a super admin user.
     */
    private function seedSuperAdmin(): void
    {
        $superAdmin = User::create([
            'name' => 'System Administrator',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@university.edu',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'global_role' => UserRole::SuperAdmin,
            'is_active' => true,
            'preferences' => [
                'theme' => 'light',
                'notifications' => true,
                'timezone' => 'America/New_York',
                'language' => 'en',
            ],
        ]);

        $this->command->info("Created Super Admin: {$superAdmin->email}");
    }

    /**
     * Create default academic departments/teams.
     */
    private function seedDefaultTeams(): void
    {
        $teams = [
            [
                'name' => 'Computer Science',
                'slug' => 'computer-science',
                'department_code' => 'CS',
                'description' => 'The Department of Computer Science offers comprehensive programs in software engineering, algorithms, data structures, and computer systems.',
                'contact_email' => 'cs@university.edu',
                'phone' => '(555) 123-4567',
                'settings' => [
                    'max_students' => 500,
                    'auto_approve' => false,
                    'allow_student_registration' => true,
                    'require_approval' => true,
                    'notification_emails' => ['cs-admin@university.edu'],
                    'business_hours' => ['start' => '08:00', 'end' => '17:00'],
                ],
            ],
            [
                'name' => 'Mathematics',
                'slug' => 'mathematics',
                'department_code' => 'MATH',
                'description' => 'The Mathematics Department provides rigorous training in pure and applied mathematics, statistics, and mathematical modeling.',
                'contact_email' => 'math@university.edu',
                'phone' => '(555) 234-5678',
                'settings' => [
                    'max_students' => 300,
                    'auto_approve' => false,
                    'allow_student_registration' => true,
                    'require_approval' => true,
                    'notification_emails' => ['math-admin@university.edu'],
                    'business_hours' => ['start' => '08:00', 'end' => '17:00'],
                ],
            ],
            [
                'name' => 'Biology',
                'slug' => 'biology',
                'department_code' => 'BIO',
                'description' => 'The Biology Department focuses on molecular biology, ecology, genetics, and life sciences research.',
                'contact_email' => 'biology@university.edu',
                'phone' => '(555) 345-6789',
                'settings' => [
                    'max_students' => 400,
                    'auto_approve' => false,
                    'allow_student_registration' => true,
                    'require_approval' => true,
                    'notification_emails' => ['bio-admin@university.edu'],
                    'business_hours' => ['start' => '08:00', 'end' => '17:00'],
                ],
            ],
        ];

        foreach ($teams as $teamData) {
            $team = Team::create($teamData);
            $this->command->info("Created team: {$team->name} ({$team->department_code})");
        }
    }

    /**
     * Create demo users for each role type.
     */
    private function seedDemoUsers(): void
    {
        // Create department admins for each team
        $teams = Team::all();
        
        foreach ($teams as $team) {
            $admin = User::create([
                'name' => ucfirst($team->slug) . ' Admin',
                'first_name' => ucfirst(explode('-', $team->slug)[0]),
                'last_name' => 'Admin',
                'email' => "admin@{$team->slug}.university.edu",
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'global_role' => UserRole::DepartmentAdmin,
                'is_active' => true,
                'current_team_id' => $team->id,
                'preferences' => [
                    'theme' => 'light',
                    'notifications' => true,
                    'timezone' => 'America/New_York',
                    'language' => 'en',
                ],
            ]);

            $this->command->info("Created Department Admin: {$admin->email} for {$team->name}");
        }

        // Create staff users
        $staffUsers = User::factory()->count(15)->staff()->withPreferences()->create();
        $this->command->info("Created {$staffUsers->count()} staff users");

        // Create student users
        $studentUsers = User::factory()->count(50)->student()->withPreferences()->create();
        $this->command->info("Created {$studentUsers->count()} student users");

        // Create some inactive users for testing
        $inactiveUsers = User::factory()->count(5)->student()->inactive()->create();
        $this->command->info("Created {$inactiveUsers->count()} inactive users");
    }

    /**
     * Create team memberships for users.
     */
    private function seedTeamMemberships(): void
    {
        $teams = Team::all();
        $departmentAdmins = User::departmentAdmins()->get();
        $staffUsers = User::staff()->get();
        $studentUsers = User::students()->active()->get();

        // Assign department admins to their teams
        foreach ($departmentAdmins as $admin) {
            if ($admin->current_team_id) {
                $team = Team::find($admin->current_team_id);
                if ($team) {
                    $team->addUser(
                        $admin,
                        TeamRole::Admin,
                        MembershipStatus::Active,
                        User::superAdmins()->first()
                    );
                    $this->command->info("Added {$admin->email} as admin to {$team->name}");
                }
            }
        }

        // Randomly assign staff to teams
        foreach ($staffUsers as $staff) {
            $assignedTeams = $teams->random(rand(1, 2)); // Each staff member joins 1-2 teams
            
            foreach ($assignedTeams as $team) {
                $role = fake()->randomElement([TeamRole::Staff, TeamRole::Admin]);
                $status = fake()->randomElement([
                    MembershipStatus::Active,
                    MembershipStatus::Active,
                    MembershipStatus::Active,
                    MembershipStatus::Pending, // 25% chance of pending
                ]);

                $approver = $status === MembershipStatus::Active 
                    ? $team->admins()->first() ?? User::superAdmins()->first()
                    : null;

                $team->addUser($staff, $role, $status, $approver);
            }
        }
        $this->command->info("Assigned staff users to teams");

        // Assign students to teams
        foreach ($studentUsers as $student) {
            $assignedTeams = $teams->random(rand(1, 3)); // Each student joins 1-3 teams
            
            foreach ($assignedTeams as $team) {
                $status = fake()->randomElement([
                    MembershipStatus::Active,
                    MembershipStatus::Active,
                    MembershipStatus::Pending, // 33% chance of pending
                ]);

                $approver = $status === MembershipStatus::Active 
                    ? $team->admins()->first() ?? User::superAdmins()->first()
                    : null;

                $permissions = null;
                if (fake()->boolean(20)) { // 20% chance of custom permissions
                    $permissions = [
                        'can_view_all_schedules' => fake()->boolean(),
                        'can_edit_own_schedule' => true,
                        'can_request_changes' => true,
                    ];
                }

                $team->users()->attach($student->id, [
                    'role' => TeamRole::Member,
                    'status' => $status,
                    'joined_at' => now(),
                    'approved_at' => $status === MembershipStatus::Active ? now() : null,
                    'approved_by' => $approver?->id,
                    'permissions' => $permissions,
                ]);
            }
        }
        $this->command->info("Assigned student users to teams");
    }

    /**
     * Create sample invitations.
     */
    private function seedInvitations(): void
    {
        $teams = Team::all();
        
        foreach ($teams as $team) {
            $admin = $team->admins()->first();
            if (!$admin) continue;

            // Create some pending invitations
            UserInvitation::factory()
                ->count(rand(2, 5))
                ->create([
                    'team_id' => $team->id,
                    'invited_by' => $admin->id,
                ]);

            // Create some expired invitations
            UserInvitation::factory()
                ->count(rand(1, 2))
                ->expired()
                ->create([
                    'team_id' => $team->id,
                    'invited_by' => $admin->id,
                ]);

            // Create some accepted invitations
            UserInvitation::factory()
                ->count(rand(1, 3))
                ->accepted()
                ->create([
                    'team_id' => $team->id,
                    'invited_by' => $admin->id,
                ]);
        }

        $this->command->info("Created sample invitations for all teams");
    }
}
