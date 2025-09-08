<?php

use App\Models\User;
use App\Models\Team;
use App\Models\UserInvitation;
use App\Enums\UserRole;
use App\Enums\TeamRole;
use App\Enums\MembershipStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Model Extensions', function () {
    test('user can be created with extended fields', function () {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'student_id' => 'STU123456',
            'global_role' => UserRole::Student,
            'is_active' => true,
            'preferences' => ['theme' => 'dark', 'notifications' => true],
        ]);

        expect($user->first_name)->toBe('John')
            ->and($user->last_name)->toBe('Doe')
            ->and($user->student_id)->toBe('STU123456')
            ->and($user->global_role)->toBe(UserRole::Student)
            ->and($user->is_active)->toBeTrue()
            ->and($user->preferences)->toBe(['theme' => 'dark', 'notifications' => true]);
    });

    test('user student_id must be unique', function () {
        User::factory()->create(['student_id' => 'STU123456']);

        expect(fn() => User::factory()->create(['student_id' => 'STU123456']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('user can have null student_id for non-student users', function () {
        $user = User::factory()->create([
            'student_id' => null,
            'global_role' => UserRole::Staff,
        ]);

        expect($user->student_id)->toBeNull()
            ->and($user->global_role)->toBe(UserRole::Staff);
    });

    test('user global_role defaults to student', function () {
        $user = User::factory()->create();

        expect($user->global_role)->toBe(UserRole::Student);
    });

    test('user is_active defaults to true', function () {
        $user = User::factory()->create();

        expect($user->is_active)->toBeTrue();
    });

    test('user last_login_at can be updated', function () {
        $user = User::factory()->create();
        $loginTime = now();

        $user->update(['last_login_at' => $loginTime]);

        expect($user->fresh()->last_login_at->toDateTimeString())
            ->toBe($loginTime->toDateTimeString());
    });
});

describe('Team Model', function () {
    test('team can be created with all fields', function () {
        $team = Team::factory()->create([
            'name' => 'Computer Science',
            'slug' => 'computer-science',
            'department_code' => 'CS',
            'description' => 'Department of Computer Science',
            'contact_email' => 'cs@university.edu',
            'phone' => '555-0123',
            'is_active' => true,
            'settings' => ['max_students' => 500, 'auto_approve' => false],
        ]);

        expect($team->name)->toBe('Computer Science')
            ->and($team->slug)->toBe('computer-science')
            ->and($team->department_code)->toBe('CS')
            ->and($team->description)->toBe('Department of Computer Science')
            ->and($team->contact_email)->toBe('cs@university.edu')
            ->and($team->phone)->toBe('555-0123')
            ->and($team->is_active)->toBeTrue()
            ->and($team->settings)->toBe(['max_students' => 500, 'auto_approve' => false]);
    });

    test('team slug must be unique', function () {
        Team::factory()->create(['slug' => 'computer-science']);

        expect(fn() => Team::factory()->create(['slug' => 'computer-science']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('team department_code must be unique', function () {
        Team::factory()->create(['department_code' => 'CS']);

        expect(fn() => Team::factory()->create(['department_code' => 'CS']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('team is_active defaults to true', function () {
        $team = Team::factory()->create();

        expect($team->is_active)->toBeTrue();
    });
});

describe('Team-User Relationships', function () {
    test('user can belong to multiple teams', function () {
        $user = User::factory()->create();
        $team1 = Team::factory()->create(['name' => 'Computer Science']);
        $team2 = Team::factory()->create(['name' => 'Mathematics']);

        $user->teams()->attach($team1, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'approved_at' => now(),
        ]);

        $user->teams()->attach($team2, [
            'role' => TeamRole::Staff,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'approved_at' => now(),
        ]);

        expect($user->teams->count())->toBe(2)
            ->and($user->teams->pluck('name')->toArray())
            ->toBe(['Computer Science', 'Mathematics']);
    });

    test('team can have multiple users', function () {
        $team = Team::factory()->create();
        $student = User::factory()->create(['global_role' => UserRole::Student]);
        $staff = User::factory()->create(['global_role' => UserRole::Staff]);

        $team->users()->attach($student, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'approved_at' => now(),
        ]);

        $team->users()->attach($staff, [
            'role' => TeamRole::Staff,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'approved_at' => now(),
        ]);

        expect($team->users->count())->toBe(2);
    });

    test('user team membership has proper pivot data', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $approver = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'permissions' => ['can_view_schedules' => true],
        ]);

        $membership = $user->teams()->first()->pivot;

        expect($membership->role)->toBe(TeamRole::Member)
            ->and($membership->status)->toBe(MembershipStatus::Active)
            ->and($membership->approved_by)->toBe($approver->id)
            ->and($membership->permissions)->toBe(['can_view_schedules' => true])
            ->and($membership->joined_at)->not->toBeNull()
            ->and($membership->approved_at)->not->toBeNull();
    });

    test('user cannot have duplicate membership in same team', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        expect(fn() => $user->teams()->attach($team, [
            'role' => TeamRole::Staff,
            'status' => MembershipStatus::Active,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('membership can have pending status', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Pending,
            'joined_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $membership = $user->teams()->first()->pivot;

        expect($membership->status)->toBe(MembershipStatus::Pending)
            ->and($membership->approved_at)->toBeNull()
            ->and($membership->approved_by)->toBeNull();
    });
});

describe('User Invitations', function () {
    test('user invitation can be created', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation = UserInvitation::factory()->create([
            'email' => 'newuser@university.edu',
            'team_id' => $team->id,
            'role' => TeamRole::Staff,
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        expect($invitation->email)->toBe('newuser@university.edu')
            ->and($invitation->team_id)->toBe($team->id)
            ->and($invitation->role)->toBe(TeamRole::Staff)
            ->and($invitation->invited_by)->toBe($inviter->id)
            ->and($invitation->token)->not->toBeNull()
            ->and(strlen($invitation->token))->toBe(64)
            ->and($invitation->expires_at)->not->toBeNull()
            ->and($invitation->accepted_at)->toBeNull()
            ->and($invitation->accepted_by)->toBeNull();
    });

    test('invitation token is unique', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation1 = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
        ]);

        $invitation2 = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
        ]);

        expect($invitation1->token)->not->toBe($invitation2->token);
    });

    test('invitation can be accepted', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);
        $accepter = User::factory()->create();

        $invitation = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $accepter->id,
        ]);

        expect($invitation->accepted_at)->not->toBeNull()
            ->and($invitation->accepted_by)->toBe($accepter->id);
    });

    test('invitation belongs to team', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
        ]);

        expect($invitation->team->id)->toBe($team->id);
    });

    test('invitation belongs to inviter user', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
        ]);

        expect($invitation->inviter->id)->toBe($inviter->id);
    });
});

describe('Database Constraints and Integrity', function () {
    test('deleting user cascades to team memberships', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        expect($team->users()->count())->toBe(1);

        $user->delete();

        expect($team->fresh()->users()->count())->toBe(0);
    });

    test('deleting team cascades to team memberships', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        expect($user->teams()->count())->toBe(1);

        $team->delete();

        expect($user->fresh()->teams()->count())->toBe(0);
    });

    test('deleting team cascades to invitations', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
        ]);

        expect(UserInvitation::count())->toBe(1);

        $team->delete();

        expect(UserInvitation::count())->toBe(0);
    });

    test('deleting approver user sets approved_by to null', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $approver = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $user->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
            'approved_by' => $approver->id,
        ]);

        $membership = $user->teams()->first()->pivot;
        expect($membership->approved_by)->toBe($approver->id);

        $approver->delete();

        $membership = $user->fresh()->teams()->first()->pivot;
        expect($membership->approved_by)->toBeNull();
    });
});

describe('Model Scopes and Query Optimization', function () {
    test('can filter active users', function () {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);

        $activeUsers = User::where('is_active', true)->get();
        $inactiveUsers = User::where('is_active', false)->get();

        expect($activeUsers->count())->toBe(2)
            ->and($inactiveUsers->count())->toBe(1);
    });

    test('can filter active teams', function () {
        Team::factory()->create(['is_active' => true]);
        Team::factory()->create(['is_active' => true]);
        Team::factory()->create(['is_active' => false]);

        $activeTeams = Team::where('is_active', true)->get();
        $inactiveTeams = Team::where('is_active', false)->get();

        expect($activeTeams->count())->toBe(2)
            ->and($inactiveTeams->count())->toBe(1);
    });

    test('can filter team memberships by status', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $team = Team::factory()->create();

        $user1->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        $user2->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Pending,
        ]);

        $activeMembers = $team->users()->wherePivot('status', MembershipStatus::Active)->get();
        $pendingMembers = $team->users()->wherePivot('status', MembershipStatus::Pending)->get();

        expect($activeMembers->count())->toBe(1)
            ->and($pendingMembers->count())->toBe(1);
    });

    test('can filter team memberships by role', function () {
        $student = User::factory()->create();
        $staff = User::factory()->create();
        $team = Team::factory()->create();

        $student->teams()->attach($team, [
            'role' => TeamRole::Member,
            'status' => MembershipStatus::Active,
        ]);

        $staff->teams()->attach($team, [
            'role' => TeamRole::Staff,
            'status' => MembershipStatus::Active,
        ]);

        $members = $team->users()->wherePivot('role', TeamRole::Member)->get();
        $staffMembers = $team->users()->wherePivot('role', TeamRole::Staff)->get();

        expect($members->count())->toBe(1)
            ->and($staffMembers->count())->toBe(1);
    });
});

describe('Enum Casting and Validation', function () {
    test('user global_role is cast to UserRole enum', function () {
        $user = User::factory()->create(['global_role' => 'student']);

        expect($user->global_role)->toBeInstanceOf(UserRole::class)
            ->and($user->global_role)->toBe(UserRole::Student);
    });

    test('team membership role is cast to TeamRole enum', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => 'staff',
            'status' => 'active',
        ]);

        $membership = $user->teams()->first()->pivot;

        expect($membership->role)->toBeInstanceOf(TeamRole::class)
            ->and($membership->role)->toBe(TeamRole::Staff);
    });

    test('team membership status is cast to MembershipStatus enum', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $user->teams()->attach($team, [
            'role' => 'member',
            'status' => 'pending',
        ]);

        $membership = $user->teams()->first()->pivot;

        expect($membership->status)->toBeInstanceOf(MembershipStatus::class)
            ->and($membership->status)->toBe(MembershipStatus::Pending);
    });

    test('invitation role is cast to TeamRole enum', function () {
        $team = Team::factory()->create();
        $inviter = User::factory()->create(['global_role' => UserRole::DepartmentAdmin]);

        $invitation = UserInvitation::factory()->create([
            'team_id' => $team->id,
            'role' => 'admin',
            'invited_by' => $inviter->id,
        ]);

        expect($invitation->role)->toBeInstanceOf(TeamRole::class)
            ->and($invitation->role)->toBe(TeamRole::Admin);
    });
});

describe('JSON Fields and Preferences', function () {
    test('user preferences are stored as JSON', function () {
        $preferences = [
            'theme' => 'dark',
            'notifications' => true,
            'timezone' => 'America/New_York',
            'dashboard_widgets' => ['calendar', 'announcements', 'grades'],
        ];

        $user = User::factory()->create(['preferences' => $preferences]);

        expect($user->preferences)->toBe($preferences)
            ->and($user->preferences['theme'])->toBe('dark')
            ->and($user->preferences['notifications'])->toBeTrue()
            ->and($user->preferences['dashboard_widgets'])->toBe(['calendar', 'announcements', 'grades']);
    });

    test('team settings are stored as JSON', function () {
        $settings = [
            'max_students' => 500,
            'auto_approve' => false,
            'notification_emails' => ['admin@cs.university.edu', 'chair@cs.university.edu'],
            'business_hours' => ['start' => '08:00', 'end' => '17:00'],
        ];

        $team = Team::factory()->create(['settings' => $settings]);

        expect($team->settings)->toBe($settings)
            ->and($team->settings['max_students'])->toBe(500)
            ->and($team->settings['auto_approve'])->toBeFalse()
            ->and($team->settings['notification_emails'])->toBe(['admin@cs.university.edu', 'chair@cs.university.edu']);
    });

    test('team membership permissions are stored as JSON', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $permissions = [
            'can_view_all_schedules' => true,
            'can_edit_schedules' => false,
            'can_manage_students' => true,
            'restricted_courses' => ['CS101', 'CS102'],
        ];

        $user->teams()->attach($team, [
            'role' => TeamRole::Staff,
            'status' => MembershipStatus::Active,
            'permissions' => $permissions,
        ]);

        $membership = $user->teams()->first()->pivot;

        expect($membership->permissions)->toBe($permissions)
            ->and($membership->permissions['can_view_all_schedules'])->toBeTrue()
            ->and($membership->permissions['restricted_courses'])->toBe(['CS101', 'CS102']);
    });
});
