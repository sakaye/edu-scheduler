<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Enums\TeamRole;

class UserInvitation extends Model
{
    /** @use HasFactory<\Database\Factories\UserInvitationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'team_id',
        'role',
        'invited_by',
        'expires_at',
        'accepted_at',
        'accepted_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (UserInvitation $invitation) {
            if (!$invitation->token) {
                $invitation->token = Str::random(64);
            }

            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Get the team this invitation is for.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent this invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted this invitation.
     */
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Scope a query to only include pending invitations.
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at');
    }

    /**
     * Scope a query to only include accepted invitations.
     */
    public function scopeAccepted(Builder $query): void
    {
        $query->whereNotNull('accepted_at');
    }

    /**
     * Scope a query to only include non-expired invitations.
     */
    public function scopeNotExpired(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired invitations.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include valid invitations (not expired and not accepted).
     */
    public function scopeValid(Builder $query): void
    {
        $query->pending()->notExpired();
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return is_null($this->accepted_at);
    }

    /**
     * Check if the invitation has been accepted.
     */
    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Check if the invitation is valid (not expired and not accepted).
     */
    public function isValid(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    /**
     * Accept the invitation.
     */
    public function accept(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        // Add user to the team with the specified role
        $this->team->addUser(
            $user,
            $this->role,
            \App\Enums\MembershipStatus::Active,
            $this->inviter
        );

        return true;
    }

    /**
     * Generate a new token for the invitation.
     */
    public function regenerateToken(): string
    {
        $token = Str::random(64);
        $this->update(['token' => $token]);

        return $token;
    }

    /**
     * Extend the expiration date.
     */
    public function extend(int $days = 7): void
    {
        $this->update(['expires_at' => now()->addDays($days)]);
    }

    /**
     * Get the invitation URL.
     */
    public function getUrlAttribute(): string
    {
        return route('invitations.accept', ['token' => $this->token]);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
