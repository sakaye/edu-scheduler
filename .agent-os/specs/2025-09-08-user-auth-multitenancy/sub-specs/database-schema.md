# Database Schema

This is the database schema implementation for the spec detailed in @.agent-os/specs/2025-09-08-user-auth-multitenancy/spec.md

> Created: 2025-09-08
> Version: 1.0.0

## Schema Changes

### Core Tables

#### 1. Users Table Modifications

The existing Laravel `users` table will be extended to support multi-tenancy and role-based access control.

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('first_name');
    $table->string('last_name');
    $table->string('student_id')->nullable()->unique();
    $table->string('global_role')->default('student');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->json('preferences')->nullable();
    
    $table->index(['global_role', 'is_active']);
    $table->index('student_id');
    $table->index('last_login_at');
});
```

**Rationale:**
- `first_name` and `last_name` separated for better data normalization and display flexibility
- `student_id` nullable to accommodate non-student users, unique for student identification
- `global_role` string column to be cast to PHP enum (UserRole::class) in the model
- `is_active` enables soft account deactivation without deletion
- `last_login_at` for security monitoring and session management
- `preferences` JSON field for user-specific UI/UX settings
- Strategic indexing for authentication queries and reporting

#### 2. Teams Table (Department/Tenant Structure)

```php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('department_code', 10)->unique();
    $table->text('description')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('settings')->nullable();
    $table->timestamps();
    
    $table->index(['is_active', 'name']);
    $table->index('department_code');
});
```

**Rationale:**
- `slug` for URL-friendly team identification in multi-tenant routing
- `department_code` for integration with academic systems (registrar, SIS)
- Contact information for administrative communications
- `is_active` enables department suspension without data loss
- `settings` JSON field for department-specific configurations
- Indexes optimize team lookup and filtering queries

#### 3. Team User Pivot Table (Many-to-Many Relationships)

```php
Schema::create('team_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('role')->default('member');
    $table->string('status')->default('pending');
    $table->timestamp('joined_at')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->json('permissions')->nullable();
    $table->timestamps();
    
    $table->unique(['team_id', 'user_id']);
    $table->index(['team_id', 'status', 'role']);
    $table->index(['user_id', 'status']);
    $table->index('approved_by');
});
```

**Rationale:**
- Team-specific `role` string column to be cast to PHP enum (TeamRole::class) in the model
- `status` string column to be cast to PHP enum (MembershipStatus::class) for approval workflow
- `joined_at` and `approved_at` provide audit trail for access management
- `approved_by` foreign key tracks which admin approved access
- `permissions` JSON field allows granular team-level permission overrides
- Composite unique constraint prevents duplicate memberships
- Strategic indexing for team member queries and permission checks

#### 4. User Invitations Table

```php
Schema::create('user_invitations', function (Blueprint $table) {
    $table->id();
    $table->string('email');
    $table->string('token', 64)->unique();
    $table->foreignId('team_id')->constrained()->onDelete('cascade');
    $table->string('role');
    $table->foreignId('invited_by')->constrained('users');
    $table->timestamp('expires_at');
    $table->timestamp('accepted_at')->nullable();
    $table->foreignId('accepted_by')->nullable()->constrained('users');
    $table->timestamps();
    
    $table->index(['email', 'team_id']);
    $table->index(['token', 'expires_at']);
    $table->index('invited_by');
});
```

**Rationale:**
- Secure token-based invitation system for staff onboarding
- Email-based invitations before user account creation
- `role` string column to be cast to PHP enum (TeamRole::class) in the model
- Expiration tracking prevents stale invitation abuse
- Audit trail tracks who sent and accepted invitations
- Team-scoped invitations support multi-department staff

#### 5. Activity Log Table (Audit Trail)

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->string('log_name');
    $table->text('description');
    $table->string('subject_type')->nullable();
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->string('event')->nullable();
    $table->string('causer_type')->nullable();
    $table->unsignedBigInteger('causer_id')->nullable();
    $table->json('properties')->nullable();
    $table->uuid('batch_uuid')->nullable();
    $table->timestamps();
    
    $table->index(['subject_type', 'subject_id']);
    $table->index(['causer_type', 'causer_id']);
    $table->index(['log_name', 'created_at']);
    $table->index('event');
});
```

**Rationale:**
- Comprehensive audit logging for security and compliance
- Polymorphic relationships support logging any model changes
- Batch UUID groups related actions (bulk operations)
- JSON properties store before/after states and metadata
- Indexes optimize audit report generation and forensic queries

### Supporting Tables

#### 6. Password Reset Tokens (Laravel Standard)

```php
Schema::create('password_reset_tokens', function (Blueprint $table) {
    $table->string('email')->primary();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});
```

#### 7. Sessions Table (Laravel Standard)

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

## Migrations

### Migration Sequence

1. **2025_01_01_000001_add_user_fields_to_users_table.php**
   - Extends existing users table with multi-tenancy fields
   - Adds indexes for performance optimization

2. **2025_01_01_000002_create_teams_table.php**
   - Creates department/tenant structure
   - Includes departmental metadata and settings

3. **2025_01_01_000003_create_team_user_table.php**
   - Establishes many-to-many user-team relationships
   - Implements role-based access within teams

4. **2025_01_01_000004_create_user_invitations_table.php**
   - Enables secure staff invitation workflow
   - Supports pre-registration team assignment

5. **2025_01_01_000005_create_activity_logs_table.php**
   - Implements comprehensive audit logging
   - Supports compliance and security monitoring

### Performance Considerations

#### Indexing Strategy

1. **Compound Indexes:**
   - `team_user(team_id, status, role)` - Optimizes team member queries
   - `users(global_role, is_active)` - Accelerates role-based filtering

2. **Single Column Indexes:**
   - `users.student_id` - Fast student lookup
   - `teams.department_code` - Academic system integration
   - `activity_logs.log_name` - Audit report performance

3. **Foreign Key Indexes:**
   - All foreign keys automatically indexed for join performance
   - Cascading deletes optimized through proper constraints

#### Query Optimization

1. **Eager Loading Patterns:**
   ```php
   // Optimized user-team relationship loading
   User::with(['teams.pivot' => function($query) {
       $query->where('status', 'active');
   }])->find($userId);
   ```

2. **Scoped Queries:**
   ```php
   // Department-scoped user queries
   $team->users()->wherePivot('status', 'active')
                 ->wherePivot('role', '!=', 'member');
   ```

### Data Integrity Constraints

#### Foreign Key Relationships

1. **Cascading Deletes:**
   - `team_user.team_id` → `teams.id` (CASCADE)
   - `team_user.user_id` → `users.id` (CASCADE)
   - `user_invitations.team_id` → `teams.id` (CASCADE)

2. **Referential Integrity:**
   - `team_user.approved_by` → `users.id` (SET NULL on delete)
   - `user_invitations.invited_by` → `users.id` (CASCADE)

#### Business Logic Constraints

1. **PHP Enum Validations:**
   - User roles validated through UserRole PHP enum class
   - Team membership status controlled through MembershipStatus PHP enum
   - Team roles enforced through TeamRole PHP enum class

   **Required PHP Enum Classes:**
   ```php
   // app/Enums/UserRole.php
   enum UserRole: string
   {
       case Student = 'student';
       case Staff = 'staff';
       case DepartmentAdmin = 'department_admin';
       case SuperAdmin = 'super_admin';
   }

   // app/Enums/TeamRole.php
   enum TeamRole: string
   {
       case Member = 'member';
       case Staff = 'staff';
       case Admin = 'admin';
   }

   // app/Enums/MembershipStatus.php
   enum MembershipStatus: string
   {
       case Pending = 'pending';
       case Active = 'active';
       case Suspended = 'suspended';
   }
   ```

2. **Unique Constraints:**
   - One team membership per user-team combination
   - Unique student IDs across system
   - Unique team slugs for routing

### Security Considerations

#### Data Protection

1. **Sensitive Data Handling:**
   - Password hashing via Laravel's bcrypt
   - Invitation tokens cryptographically secure
   - Session data encrypted in database

2. **Access Control:**
   - Row-level security via team membership
   - Role-based query scoping
   - Audit logging for sensitive operations

3. **Privacy Compliance:**
   - User preferences stored as JSON for flexibility
   - Activity logs respect data retention policies
   - Personal data isolated in user profiles

#### Migration Rollback Safety

All migrations include proper `down()` methods for safe rollbacks:

```php
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropIndex(['global_role', 'is_active']);
        $table->dropColumn(['first_name', 'last_name', 'student_id', 
                           'global_role', 'is_active', 'last_login_at', 'preferences']);
    });
}
```

This schema design supports the full multi-tenant authentication system while maintaining performance, security, and data integrity standards required for an educational scheduling application.