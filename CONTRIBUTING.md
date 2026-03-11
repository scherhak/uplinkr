# Contributing

Thanks for your interest in contributing to Uplinkr! We appreciate your help in making this package better.

## Core Principles

Before contributing, please understand Uplinkr's design philosophy:

- **Minimalism First:** Keep the package lightweight and focused. We deliberately avoid feature bloat.
- **CLI-Centric:** Commands are an essential part of the package. All features should be accessible via CLI.
- **File-Based:** No database dependencies. Storage remains JSON-based.
- **Explicit Over Implicit:** Behavior should stay understandable and operationally transparent.
- **Clear Separation of Concerns:** Package configuration belongs in `config/uplinkr.php`; selected runtime state belongs in file-based storage such as `uplinkr/settings.json`.
- **Laravel Integration:** Tight integration with Laravel's ecosystem (scheduler, mail, etc.)

## Before You Start

- **Check existing issues and discussions** to avoid duplicate work
- **Open an issue first** for bugs or feature proposals before starting work
- **For major changes:** Please discuss your ideas in an issue before investing significant time
- **Small PRs are preferred** over large, sweeping changes

## Development Setup

**Requirements:**
- PHP 8.4 or newer
- Composer
- Laravel 12.x

**Installation:**
```bash
composer install
```

**Run Tests:**
```bash
./vendor/bin/phpunit
```

**Code Style:**
```bash
./vendor/bin/pint
```

## Pull Request Guidelines

### Required

- ✅ **Target the `main` branch** - All PRs must be made against `main`
- ✅ **Tests must pass** - All existing tests must pass, and new features require tests
- ✅ **Tests must be implemented** - New functionality requires test coverage
- ✅ **Follow the minimalist approach** - Avoid unnecessary complexity or dependencies
- ✅ **Maintain CLI conventions** - Commands must follow existing patterns and naming
- ✅ **Respect file-based architecture** - Keep project/global state human-inspectable and JSON-based

### Best Practices

- **Keep changes focused:** One feature or fix per PR
- **Write clear commit messages:** Use descriptive, imperative mood (e.g., "Add probe timeout option")
- **Update documentation:** Include relevant updates to comments and README if needed
- **Update user-facing docs when needed:** If behavior, commands, config, scheduling, or heartbeat flows change, update the relevant docs/changelog too
- **Follow existing code style:** Use Laravel Pint for formatting
- **Test edge cases:** Consider failure scenarios and validation
- **CLI output consistency:** Match the tone and formatting of existing commands
- **Be careful with scheduler/heartbeat behavior:** Changes to scheduling, queue timing, or `I'm alive` semantics require extra scrutiny

### PR Description

Please include in your PR:

- What problem does this solve?
- What changes were made?
- How to test the changes?
- Any breaking changes or deprecations?

## Versioning Policy

Uplinkr follows semantic versioning: `MAJOR.MINOR.PATCH`.

### PATCH (x.y.Z)
Use PATCH for:
- Bug fixes and stability improvements
- Error handling and resilience improvements
- Logging and observability enhancements
- Internal refactoring without user-facing behavior changes
- Test additions or fixes
- Documentation updates

**Rule:**  
No new features and no user-facing behavior changes such as new command options, changed storage semantics, or altered scheduling flows.

### MINOR (x.Y.z)
Use MINOR for:
- New features or capabilities
- New CLI commands or command options
- New or extended configuration options
- New global settings / runtime behavior
- Backward-compatible behavior improvements

**Rule:**  
Existing setups must continue to work without manual changes.

### MAJOR (X.y.z)
Use MAJOR for:
- Breaking CLI changes (renamed or removed commands/flags)
- Configuration format changes
- Storage layout changes
- Feature removals requiring migration

**Rule:**  
Manual user action is required.

## Code Standards

- Follow PSR-12 coding standards (enforced by Pint)
- Use type hints for parameters and return types
- Write clear, self-documenting code
- Add PHPDoc blocks for complex logic
- Keep methods small and focused

## Testing

- Write unit tests for business logic
- Write feature tests for commands
- Test both success and failure paths
- Mock external dependencies (HTTP requests, file system where appropriate)
- Cover file-based state transitions where behavior depends on stored JSON data
- Add targeted tests for scheduler-/queue-sensitive behavior when changing execution timing or heartbeat logic

## Questions?

If you have questions about contributing, feel free to:

- Open a discussion on GitHub
- Comment on relevant issues
- Reach out via email: sascha@uplinkr.dev

## Code of Conduct

- Be respectful and constructive
- Focus on what is best for the community
- Show empathy towards other contributors

---

Thank you for contributing to Uplinkr.
