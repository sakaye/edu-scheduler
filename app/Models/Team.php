<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\TeamRole;
use App\Enums\MembershipStatus;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'department_code',
        'description',
        'contact_email',
        'phone',
        'is_active',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Get users that belong to this team.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
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
     * Get active users that belong to this team.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', MembershipStatus::Active);
    }

    /**
     * Get pending users waiting for approval.
     */
    public function pendingUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', MembershipStatus::Pending);
    }

    /**
     * Get team members (students).
     */
    public function members(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', TeamRole::Member);
    }

    /**
     * Get team staff.
     */
    public function staff(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', TeamRole::Staff);
    }

    /**
     * Get team admins.
     */
    public function admins(): BelongsToMany
    {
        return $this->activeUsers()->wherePivot('role', TeamRole::Admin);
    }

    /**
     * Get invitations for this team.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    /**
     * Get pending invitations for this team.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at');
    }

    /**
     * Scope a query to only include active teams.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive teams.
     */
    public function scopeInactive(Builder $query): void
    {
        $query->where('is_active', false);
    }

    /**
     * Get team statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->activeUsers()->count(),
            'pending_users' => $this->pendingUsers()->count(),
            'members' => $this->members()->count(),
            'staff' => $this->staff()->count(),
            'admins' => $this->admins()->count(),
            'pending_invitations' => $this->pendingInvitations()->count(),
        ];
    }

    /**
     * Check if a user is a member of this team.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is an active member of this team.
     */
    public function hasActiveMember(User $user): bool
    {
        return $this->activeUsers()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the user's role in this team.
     */
    public function getUserRole(User $user): ?TeamRole
    {
        $membership = $this->users()->where('user_id', $user->id)->first();

        return $membership ? $membership->pivot->role : null;
    }

    /**
     * Get the user's status in this team.
     */
    public function getUserStatus(User $user): ?MembershipStatus
    {
        $membership = $this->users()->where('user_id', $user->id)->first();

        return $membership ? $membership->pivot->status : null;
    }

    /**
     * Add a user to the team with specified role and status.
     */
    public function addUser(
        User $user,
        TeamRole $role = TeamRole::Member,
        MembershipStatus $status = MembershipStatus::Pending,
        ?User $approver = null
    ): void {
        $pivotData = [
            'role' => $role,
            'status' => $status,
            'joined_at' => now(),
        ];

        if ($status === MembershipStatus::Active && $approver) {
            $pivotData['approved_at'] = now();
            $pivotData['approved_by'] = $approver->id;
        }

        $this->users()->attach($user->id, $pivotData);
    }

    /**
     * Approve a pending user membership.
     */
    public function approveUser(User $user, User $approver): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('status', MembershipStatus::Pending)
            ->update([
                'status' => MembershipStatus::Active,
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]) > 0;
    }

    /**
     * Suspend a user's membership.
     */
    public function suspendUser(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->update(['status' => MembershipStatus::Suspended]) > 0;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
