# Contributing

Thanks for helping improve Laravel UUIDable. Contributions are welcome through issues and pull requests in the [GitHub repository](https://github.com/EvanSchleret/laravel-uuidable).

## Requirements

- PHP 8.3 or later
- Composer
- Laravel 12 or 13 for integration testing

## Local setup

Clone the repository and install the development dependencies:

```bash
composer install
```

## Checks

Run the Pest test suite and formatter before opening a pull request:

```bash
composer test
composer format:check
composer validate --no-check-publish
```

Add or update tests for behavior changes. Keep contributions focused and document changes to the public API.

## Pull requests

- Describe the problem and the proposed change.
- Include focused tests and update the README when public behavior changes.
- Call out breaking changes and migration steps.
- Keep the pull request scoped to one feature or fix where practical.

## Questions

Open an issue in the [GitHub repository](https://github.com/EvanSchleret/laravel-uuidable/issues) for questions or feature discussions.
