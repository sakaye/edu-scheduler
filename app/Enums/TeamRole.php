<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TeamRole: string implements HasColor, HasLabel
{
    case Member = 'member';
    case Staff = 'staff';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Staff => 'Staff',
            self::Admin => 'Admin',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Member => 'gray',
            self::Staff => 'success',
            self::Admin => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Member => 'Basic team membership with limited permissions',
            self::Staff => 'Team staff with enhanced permissions for course management',
            self::Admin => 'Team administrators with full team management capabilities',
        };
    }
}