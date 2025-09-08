<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Enums\UserRole;
use App\Enums\TeamRole;
use App\Enums\MembershipStatus;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'student_id',
        'global_role',
        'is_active',
        'last_login_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'global_role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'global_role' => UserRole::Student,
        'is_active' => true,
    ];

    /**
     * Get teams that the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot([
                'role',
                'status',
                'joined_at',
                'approved_at',
                'approved_by',
                'permissions',
            ])
            ->withTimestamps()
            ->using(TeamUser::class);
    }

    /**
     * Get active teams that the user belongs to.
     */
    public function activeTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('status', MembershipStatus::Active);
    }

    /**
     * Get pending team memberships.
     */
    public function pendingTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('status', MembershipStatus::Pending);
    }

    /**
     * Get teams where user is an admin.
     */
    public function adminTeams(): BelongsToMany
    {
        return $this->activeTeams()->wherePivot('role', TeamRole::Admin);
    }

    /**
     * Get teams where user is staff.
     */
    public function staffTeams(): BelongsToMany
    {
        return $this->activeTeams()->wherePivot('role', TeamRole::Staff);
    }

    /**
     * Get teams where user is a member.
     */
    public function memberTeams(): BelongsToMany
    {
        return $this->activeTeams()->wherePivot('role', TeamRole::Member);
    }

    /**
     * Get invitations sent by this user.
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'invited_by');
    }

    /**
     * Get team memberships approved by this user.
     */
    public function approvedMemberships(): HasMany
    {
        return $this->hasMany(TeamUser::class, 'approved_by');
    }

    /**
     * Get the user's current team.
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive(Builder $query): void
    {
        $query->where('is_active', false);
    }

    /**
     * Scope a query to filter users by global role.
     */
    public function scopeWithGlobalRole(Builder $query, UserRole $role): void
    {
        $query->where('global_role', $role);
    }

    /**
     * Scope a query to filter students.
     */
    public function scopeStudents(Builder $query): void
    {
        $query->where('global_role', UserRole::Student);
    }

    /**
     * Scope a query to filter staff.
     */
    public function scopeStaff(Builder $query): void
    {
        $query->where('global_role', UserRole::Staff);
    }

    /**
     * Scope a query to filter department admins.
     */
    public function scopeDepartmentAdmins(Builder $query): void
    {
        $query->where('global_role', UserRole::DepartmentAdmin);
    }

    /**
     * Scope a query to filter super admins.
     */
    public function scopeSuperAdmins(Builder $query): void
    {
        $query->where('global_role', UserRole::SuperAdmin);
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }

        return $this->name ?? 'Unknown User';
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        }

        return Str::of($this->name ?? 'U')
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Check if user is a student.
     */
    public function isStudent(): bool
    {
        return $this->global_role === UserRole::Student;
    }

    /**
     * Check if user is staff.
     */
    public function isStaff(): bool
    {
        return $this->global_role === UserRole::Staff;
    }

    /**
     * Check if user is a department admin.
     */
    public function isDepartmentAdmin(): bool
    {
        return $this->global_role === UserRole::DepartmentAdmin;
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->global_role === UserRole::SuperAdmin;
    }

    /**
     * Check if user can manage teams globally.
     */
    public function canManageTeams(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can manage a specific team.
     */
    public function canManageTeam(Team $team): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $membership = $this->teams()->where('team_id', $team->id)->first();

        return $membership && 
               $membership->pivot->status === MembershipStatus::Active &&
               $membership->pivot->role === TeamRole::Admin;
    }

    /**
     * Check if user is an active member of a team.
     */
    public function isMemberOf(Team $team): bool
    {
        return $this->activeTeams()->where('team_id', $team->id)->exists();
    }

    /**
     * Get user's role in a specific team.
     */
    public function getTeamRole(Team $team): ?TeamRole
    {
        $membership = $this->teams()->where('team_id', $team->id)->first();

        return $membership ? $membership->pivot->role : null;
    }

    /**
     * Get user's status in a specific team.
     */
    public function getTeamStatus(Team $team): ?MembershipStatus
    {
        $membership = $this->teams()->where('team_id', $team->id)->first();

        return $membership ? $membership->pivot->status : null;
    }

    /**
     * Join a team with specified role.
     */
    public function joinTeam(
        Team $team,
        TeamRole $role = TeamRole::Member,
        MembershipStatus $status = MembershipStatus::Pending,
        ?User $approver = null
    ): void {
        $team->addUser($this, $role, $status, $approver);
    }

    /**
     * Leave a team.
     */
    public function leaveTeam(Team $team): void
    {
        $this->teams()->detach($team->id);
    }

    /**
     * Switch current team context.
     */
    public function switchToTeam(Team $team): bool
    {
        if (!$this->isMemberOf($team)) {
            return false;
        }

        $this->update(['current_team_id' => $team->id]);

        return true;
    }

    /**
     * Get user preferences with defaults.
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences, $key, $default);
    }

    /**
     * Set a user preference.
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->update(['preferences' => $preferences]);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Check if user has verified email.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get user statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_teams' => $this->teams()->count(),
            'active_teams' => $this->activeTeams()->count(),
            'pending_teams' => $this->pendingTeams()->count(),
            'admin_teams' => $this->adminTeams()->count(),
            'staff_teams' => $this->staffTeams()->count(),
            'member_teams' => $this->memberTeams()->count(),
            'sent_invitations' => $this->sentInvitations()->count(),
            'approved_memberships' => $this->approvedMemberships()->count(),
        ];
    }
}
