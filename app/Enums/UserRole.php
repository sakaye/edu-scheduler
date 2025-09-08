<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Student = 'student';
    case Staff = 'staff';
    case DepartmentAdmin = 'department_admin';
    case SuperAdmin = 'super_admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Staff => 'Staff',
            self::DepartmentAdmin => 'Department Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Student => 'info',
            self::Staff => 'success',
            self::DepartmentAdmin => 'warning',
            self::SuperAdmin => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Student => 'Students can view schedules and manage their profiles',
            self::Staff => 'Staff can manage assigned courses and view team data',
            self::DepartmentAdmin => 'Department administrators can manage users and team settings',
            self::SuperAdmin => 'Super administrators have system-wide access',
        };
    }
}