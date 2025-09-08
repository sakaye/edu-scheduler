<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TeamRole;
use App\Enums\MembershipStatus;

class TeamUser extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_user';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'status' => MembershipStatus::class,
            'joined_at' => 'datetime',
            'approved_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'status',
        'joined_at',
        'approved_at',
        'approved_by',
        'permissions',
    ];

    /**
     * Get the user that owns this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that this membership belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who approved this membership.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active;
    }

    /**
     * Check if the membership is pending.
     */
    public function isPending(): bool
    {
        return $this->status === MembershipStatus::Pending;
    }

    /**
     * Check if the membership is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === MembershipStatus::Suspended;
    }

    /**
     * Check if the user is an admin in this team.
     */
    public function isAdmin(): bool
    {
        return $this->role === TeamRole::Admin;
    }

    /**
     * Check if the user is staff in this team.
     */
    public function isStaff(): bool
    {
        return $this->role === TeamRole::Staff;
    }

    /**
     * Check if the user is a member in this team.
     */
    public function isMember(): bool
    {
        return $this->role === TeamRole::Member;
    }

    /**
     * Get a specific permission value.
     */
    public function hasPermission(string $permission): bool
    {
        return (bool) ($this->permissions[$permission] ?? false);
    }

    /**
     * Set a specific permission.
     */
    public function setPermission(string $permission, bool $value): void
    {
        $permissions = $this->permissions ?? [];
        $permissions[$permission] = $value;
        $this->update(['permissions' => $permissions]);
    }
}
