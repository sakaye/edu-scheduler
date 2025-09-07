# Laravel Code Style Guide

## Context

Laravel-specific code style rules for edu-scheduler application. This follows Laravel's conventions and uses Laravel Pint for code formatting.

<conditional-block context-check="general-formatting">
IF this General Formatting section already read in current context:
  SKIP: Re-reading this section
  NOTE: "Using General Formatting rules already in context"
ELSE:
  READ: The following formatting rules

## General Formatting

### Code Style Enforcement
- Run `vendor/bin/pint` before finalizing changes to ensure code matches project standards
- Laravel adheres to PSR-12 coding standard with Laravel-specific extensions
- Use `vendor/bin/pint --test` to check for style violations without making changes

### Indentation
- Use 4 spaces for indentation (PSR-12 standard)
- Maintain consistent indentation throughout files
- Align chained method calls and array elements for readability

### Naming Conventions
- **Classes**: Use PascalCase (e.g., `UserProfile`, `PaymentProcessor`)
- **Methods and Variables**: Use camelCase (e.g., `getUserProfile`, `calculateTotal`)
- **Database Tables**: Use snake_case plural (e.g., `user_profiles`, `payment_methods`)
- **Database Columns**: Use snake_case (e.g., `first_name`, `created_at`)
- **Constants**: Use UPPER_SNAKE_CASE (e.g., `MAX_RETRY_COUNT`)
- **Enum Cases**: Use PascalCase (e.g., `FavoritePerson`, `BestLake`, `Monthly`)

### String Formatting
- Use single quotes for simple strings: `'Hello World'`
- Use double quotes when variable interpolation is needed: `"Hello {$name}"`
- Use heredoc/nowdoc for multi-line strings when appropriate

### Array Syntax
- Always use short array syntax: `[]` instead of `array()`
- Align array elements for multi-line arrays

### Code Comments
- Prefer PHPDoc blocks over inline comments
- Add useful array shape type definitions for arrays when appropriate
- Document the "why" behind complex business logic
- Never use comments within code unless something is very complex
- Update comments when modifying code to maintain accuracy
</conditional-block>

<conditional-block task-condition="php-laravel" context-check="php-style">
IF current task involves writing or updating PHP/Laravel code:
  IF php-style rules already in context:
    SKIP: Re-reading this section
    NOTE: "Using PHP/Laravel style rules already in context"
  ELSE:
    READ: The following PHP/Laravel specific rules

## PHP Style Rules

### Constructors
- Use PHP 8+ constructor property promotion: `public function __construct(public GitHub $github) {}`
- Do not allow empty `__construct()` methods with zero parameters

### Type Declarations
- Always use explicit return type declarations for methods and functions
- Use appropriate PHP type hints for method parameters
- Example: `protected function isAccessible(User $user, ?string $path = null): bool`

### PHPDoc Blocks
- Add useful array shape type definitions: `@return array<int, \\Illuminate\\Mail\\Mailables\\Attachment>`
- Omit redundant `@param` or `@return` when native type hints are sufficient
- Use two spaces after attribute and type in PHPDoc
- Format example:
```php
/**
 * Register a binding with the container.
 *
 * @param  string|array  $abstract
 * @param  \\Closure|string|null  $concrete
 * @param  bool  $shared
 * @return void
 *
 * @throws \\Exception
 */
```

### Control Structures
- Always use curly braces for control structures, even single-line blocks
- Format example:
```php
if ($condition) {
    // Single line still gets braces
}
```

### Enum Usage
- **CRITICAL**: Always use PHP enum classes, never database column types for enums
- Enum cases should use PascalCase: `FavoritePerson`, `BestLake`, `Monthly`
- When used in Filament resources, implement `HasColor` and `HasLabel` contracts:
```php
use Filament\\Support\\Contracts\\{HasColor, HasLabel};

enum Status: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }
}
```
ELSE:
  SKIP: PHP/Laravel style rules not relevant to current task
</conditional-block>

<conditional-block task-condition="blade-frontend" context-check="blade-style">
IF current task involves writing or updating Blade templates or frontend code:
  IF blade-style rules already in context:
    SKIP: Re-reading this section
    NOTE: "Using Blade/frontend style rules already in context"
  ELSE:
    READ: The following Blade and frontend rules

## Blade Template Style

### Blade Directives
- Use `{{ $variable }}` for displaying data (automatic HTML entity encoding)
- Use `{!! $variable !!}` only when you need to output raw HTML (rare)
- Use `@php` directive sparingly for simple inline logic:
```blade
@php
    $counter = 1;
@endphp
```

### Blade Components
- Use Flux UI components when available: `<flux:button variant="primary"/>`
- Fallback to standard Blade components if Flux is unavailable
- Follow existing component conventions in the project

### TailwindCSS
- Use Tailwind v4+ syntax and utilities
- Use `gap` utilities for spacing instead of margins in flex/grid layouts
- Support dark mode using `dark:` prefix when other components do
- Avoid deprecated utilities (use `bg-black/20` not `bg-opacity-20`)

### Livewire/Volt Style
- Single root element requirement for Livewire components
- Use `wire:loading` and `wire:dirty` for loading states
- Add `wire:key` in loops for proper tracking
- Use `wire:model.live` for real-time updates, `wire:model` for deferred

ELSE:
  SKIP: Blade/frontend style rules not relevant to current task
</conditional-block>

<conditional-block task-condition="filament" context-check="filament-style">
IF current task involves writing or updating Filament code:
  IF filament-style rules already in context:
    SKIP: Re-reading this section
    NOTE: "Using Filament style rules already in context"
  ELSE:
    READ: The following Filament-specific rules

## Filament Style Rules

### Component Organization
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`  
- Table filters: `Tables/Filters/`
- Actions: `Actions/`

### Static Make Methods
- Utilize static `make()` methods for consistent component initialization
- Example: `Forms\\Components\\TextInput::make('name')`

### Relationships
- Use `relationship()` method on form components when you need options:
```php
Forms\\Components\\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required()
```

### Artisan Commands
- Always use Filament-specific Artisan commands to create components
- Always pass `--no-interaction` to prevent user prompts
- Check available commands with `list-artisan-commands` tool

ELSE:
  SKIP: Filament style rules not relevant to current task
</conditional-block>