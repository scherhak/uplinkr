# AGENTS.md

## Project Name
Uplinkr

## Project Overview
Uplinkr is a lightweight Laravel 12 package for website and API monitoring.
It is designed as a CLI-first tool with a strong focus on stability,
predictability, and minimal dependencies.
It uses file-based JSON storage for both project data and selected global
runtime settings such as heartbeat configuration.

The project is currently in an early but structured MVP phase.
Backward compatibility and clean architecture are important.

## Core Principles
- CLI-first, not dashboard-first
- Explicit over implicit behavior
- Clear separation between package config and runtime state
- Predictable execution and output
- Minimal magic, minimal abstraction
- Stability over cleverness

## Tech Stack
- PHP 8.4
- Laravel 12
- Laravel Console Commands
- Laravel Scheduler / Queue integration
- File-based storage (JSON) in early versions
- No frontend, no SPA, no JS framework

## Allowed Actions
You MAY:
- Add new Laravel console commands when explicitly requested
- Add new classes, services, or enums if they fit the existing architecture
- Improve readability, naming, and inline documentation
- Suggest refactorings **without applying them automatically**
- Extend configuration files when explicitly requested
- Add small, isolated helper classes if clearly justified
- Extend existing heartbeat / notification flows when explicitly requested and aligned with the current channel architecture

## Restricted Actions (Ask First)
You MUST ASK BEFORE:
- Refactoring existing architecture or folder structure
- Renaming namespaces, commands, or public classes
- Changing command signatures or CLI output formats
- Introducing new storage mechanisms (DB, Redis, etc.)
- Adding new notification channels or integrations
- Modifying scheduler or background execution behavior
- Changing global settings behavior or heartbeat scheduling semantics

## Forbidden Actions
You MUST NOT:
- Introduce breaking changes without explicit approval
- Change existing public APIs or command behavior silently
- Add frontend frameworks, dashboards, or UI layers
- Add heavy dependencies or meta-frameworks
- Replace file-based storage unless explicitly instructed
- Perform large-scale refactorings on your own initiative

## Coding Standards
- Strict typing where possible
- Explicit return types
- Small, focused classes
- Prefer services over traits
- Enums are preferred over string constants
- No hidden side effects
- No global state
- File-based state should remain human-inspectable and operationally transparent

## CLI Output Rules
- CLI output must be clear, concise, and human-readable
- Icons and symbols may be used sparingly (✔ ⚠ ✖)
- Do not change existing CLI wording unless requested
- Consistency across commands is critical

## Workflow Expectations
- Explain your plan before making changes
- Keep changes minimal and scoped
- Provide isolated code snippets or diffs
- Prefer incremental improvements over rewrites
- If unsure, ask instead of guessing

## Versioning Awareness
- Patch versions are for fixes and minor improvements
- Minor versions may include structural cleanup
- Major versions may include breaking changes (explicitly announced)

## Mindset
Behave like a careful senior developer contributing to an
open-source monitoring tool:
conservative, explicit, and respectful of existing design decisions.
