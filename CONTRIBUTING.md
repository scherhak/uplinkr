# Contributing

Thanks for your interest in contributing to Uplinkr! We appreciate your help in making this package better.

## Core Principles

Before contributing, please understand Uplinkr's design philosophy:

- **Minimalism First:** Keep the package lightweight and focused. We deliberately avoid feature bloat.
- **CLI-Centric:** Commands are an essential part of the package. All features should be accessible via CLI.
- **File-Based:** No database dependencies. Storage remains JSON-based.
- **Laravel Integration:** Tight integration with Laravel's ecosystem (scheduler, mail, etc.)

## Before You Start

- **Check existing issues and discussions** to avoid duplicate work
- **Open an issue first** for bugs or feature proposals before starting work
- **For major changes:** Please discuss your ideas in an issue before investing significant time
- **Small PRs are preferred** over large, sweeping changes

## Development Setup

**Requirements:**
- PHP 8.2 or newer
- Composer
- Laravel 11.x

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

### Best Practices

- **Keep changes focused:** One feature or fix per PR
- **Write clear commit messages:** Use descriptive, imperative mood (e.g., "Add probe timeout option")
- **Update documentation:** Include relevant updates to comments and README if needed
- **Follow existing code style:** Use Laravel Pint for formatting
- **Test edge cases:** Consider failure scenarios and validation
- **CLI output consistency:** Match the tone and formatting of existing commands

### PR Description

Please include in your PR:

- What problem does this solve?
- What changes were made?
- How to test the changes?
- Any breaking changes or deprecations?

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

Thank you for contributing to Uplinkr! 🚀
