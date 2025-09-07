# Laravel Development Best Practices

## Context

Laravel-specific development guidelines for edu-scheduler application.

<conditional-block context-check="core-principles">
IF this Core Principles section already read in current context:
  SKIP: Re-reading this section
  NOTE: "Using Core Principles already in context"
ELSE:
  READ: The following principles

## Core Principles

### Do Things the Laravel Way
- Use `php artisan make:` commands to create new files (migrations, controllers, models)
- Follow Laravel's conventions and directory structure
- Pass `--no-interaction` to all Artisan commands for automation

### Keep It Simple
- Implement code in the fewest lines possible
- Avoid over-engineering solutions  
- Choose Laravel's built-in features over custom implementations

### Optimize for Readability
- Use descriptive names for variables and methods: `isRegisteredForDiscounts`, not `discount()`
- Write self-documenting code with clear variable names
- Add PHPDoc for complex business logic

### DRY (Don't Repeat Yourself)
- Extract repeated business logic to private methods
- Create reusable Blade components for repeated UI patterns
- Use Laravel's helper functions and utilities

### File Structure
- Stick to Laravel's existing directory structure
- Don't create new base folders without approval
- Keep files focused on single responsibility
- Check sibling files for correct structure and naming conventions
</conditional-block>

<conditional-block context-check="laravel-practices" task-condition="laravel-development">
IF current task involves Laravel development:
  IF Laravel practices already read in current context:
    SKIP: Re-reading this section
    NOTE: "Using Laravel practices already in context"
  ELSE:
    READ: The following Laravel-specific practices

## Laravel Best Practices

### Database & Eloquent
- Always use proper Eloquent relationship methods with return type hints
- Prefer relationship methods over raw queries or manual joins
- Use Eloquent models and relationships before raw database queries
- Avoid `DB::`; prefer `Model::query()` for complex operations
- Generate code that prevents N+1 query problems using eager loading
- When modifying columns in migrations, include all existing attributes to prevent data loss

### Model Creation
- When creating models, also create useful factories and seeders
- Use `php artisan make:model --help` to see all available options
- Check if factories have custom states before manually setting up models

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation
- Include both validation rules and custom error messages in Form Requests
- Check sibling Form Requests for array vs string validation rule conventions

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization (gates, policies, Sanctum)
- Check user permissions with `cannot()` method before sensitive operations

### Configuration & Environment
- Use environment variables only in configuration files
- Never use `env()` function directly outside of config files
- Always use `config('app.name')`, not `env('APP_NAME')`

### URL Generation
- Prefer named routes and `route()` function for generating links
- Use `get-absolute-url` tool to ensure correct scheme/domain/port

### Testing
- When creating models for tests, use factories with custom states when available
- Use `$this->faker->word()` or `fake()->randomDigit()` following existing conventions
- Most tests should be feature tests, use `--unit` flag only when appropriate

ELSE:
  SKIP: Laravel practices not relevant to current task
</conditional-block>

<conditional-block context-check="filament-practices" task-condition="filament-development">
IF current task involves Filament development:
  IF Filament practices already read in current context:
    SKIP: Re-reading this section
    NOTE: "Using Filament practices already in context"
  ELSE:
    READ: The following Filament-specific practices

## Filament Best Practices

### Resource Organization
- Resources typically live in `app/Filament/Resources`
- Use Filament-specific Artisan commands with `--no-interaction`
- Check available commands with `list-artisan-commands` tool

### Forms & Components
- Use static `make()` methods for consistent component initialization
- Use `relationship()` method on form components when you need options
- Check for existing components to reuse before writing new ones

### Testing Filament
- Ensure authentication in tests before accessing Filament pages
- Use `livewire()` or `Livewire::test()` for assertions
- Test examples:
  - Table: `livewire(ListUsers::class)->assertCanSeeTableRecords($users)`
  - Create: `livewire(CreateUser::class)->fillForm([...])->call('create')`
  - Actions: `livewire(EditInvoice::class)->callAction('send')`

### File Visibility & v4 Changes
- File visibility is `private` by default in Filament v4
- Table filters are deferred by default (users must click apply button)
- Use `deferFilters(false)` to disable deferred behavior if needed
- Grid, Section, and Fieldset no longer span all columns by default

ELSE:
  SKIP: Filament practices not relevant to current task
</conditional-block>

<conditional-block context-check="livewire-volt-practices" task-condition="livewire-volt-development">
IF current task involves Livewire/Volt development:
  IF Livewire/Volt practices already read in current context:
    SKIP: Re-reading this section
    NOTE: "Using Livewire/Volt practices already in context"
  ELSE:
    READ: The following Livewire/Volt practices

## Livewire/Volt Best Practices

### Component Structure
- Use `php artisan make:livewire` with `--no-interaction` for new components
- Create Volt components with `php artisan make:volt [name] [--test] [--pest]`
- State should live on the server, with UI reflecting it
- Always validate form data and run authorization checks in Livewire actions

### Performance & UX
- Use `wire:loading` and `wire:dirty` for delightful loading states
- Add `wire:key` in loops for proper element tracking
- Use `wire:model.live` for real-time updates, `wire:model` for deferred updates
- Use lifecycle hooks like `mount()` and `updatedFoo()` for initialization

### Volt Class-Based Components
- Use class-based approach: `new class extends Component`
- Utilize all Livewire features with traditional syntax
- Example structure:
```php
use Livewire\Volt\Component;

new class extends Component {
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }
} ?>

<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
```

### Testing Volt Components
- Use existing test directory or create `tests/Feature/Volt`
- Use `Volt::test('counter')` for testing components
- Test with authentication: `Volt::test('component')->actingAs($user)`

ELSE:
  SKIP: Livewire/Volt practices not relevant to current task
</conditional-block>

<conditional-block context-check="pest-practices" task-condition="pest-testing">
IF current task involves Pest testing:
  IF Pest practices already read in current context:
    SKIP: Re-reading this section
    NOTE: "Using Pest practices already in context"
  ELSE:
    READ: The following Pest testing practices

## Pest Testing Best Practices

### Test Creation & Organization
- All tests must be written using Pest
- Use `php artisan make:test --pest <name>` for feature tests
- Use `php artisan make:test --pest --unit <name>` for unit tests
- Never remove tests without approval - they are core to the application

### Test Structure & Assertions
- Test all happy paths, failure paths, and edge cases
- Use specific assertion methods: `assertForbidden()` not `assertStatus(403)`
- When asserting JSON responses: `$response->assertSuccessful()`

### Running Tests
- Run minimal tests using filters: `php artisan test --filter=testName`
- Run specific files: `php artisan test tests/Feature/ExampleTest.php`
- Run all tests: `php artisan test`

### Advanced Features
- Use datasets for testing multiple scenarios with same logic
- Use `mock()` or `$this->mock()` for mocking dependencies
- Leverage browser testing for complex UI interactions
- Example dataset:
```php
it('validates emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

ELSE:
  SKIP: Pest testing practices not relevant to current task
</conditional-block>

<conditional-block context-check="dependencies" task-condition="choosing-external-library">
IF current task involves choosing an external library:
  IF Dependencies section already read in current context:
    SKIP: Re-reading this section
    NOTE: "Using Dependencies guidelines already in context"
  ELSE:
    READ: The following guidelines

## Dependencies

### Choose Libraries Wisely
When adding third-party dependencies:
- Select the most popular and actively maintained option
- Check the library's GitHub repository for:
  - Recent commits (within last 6 months)
  - Active issue resolution
  - Number of stars/downloads
  - Clear documentation
- Don't change application dependencies without approval

ELSE:
  SKIP: Dependencies section not relevant to current task
</conditional-block>