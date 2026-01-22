# Tests

This directory contains comprehensive tests for the FULLHAUS PHP-CS-Fixer configuration.

## Test Structure

### Unit Tests

- **CsFixerConfigTest.php**: Tests the configuration class itself, verifying that all rules are properly set.

### Integration Tests

- **StyleValidationTest.php**: Tests individual style rules by applying fixers to code samples.
- **IntegrationTest.php**: End-to-end tests that run PHP-CS-Fixer on fixture files.

### Fixtures

The `Fixtures/` directory contains example files:

- **GoodExample.php**: A file that follows all FULLHAUS coding standards.
- **BadExample.php**: A file with intentional violations for testing.

## Running Tests

### Install Dependencies

First, install the required dependencies:

```bash
composer install
```

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Run only unit tests
vendor/bin/phpunit tests/CsFixerConfigTest.php

# Run only style validation tests
vendor/bin/phpunit tests/StyleValidationTest.php

# Run only integration tests
vendor/bin/phpunit tests/IntegrationTest.php
```

### Run with Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/
```

## What Is Tested

The test suite validates the following aspects of the FULLHAUS PHP-CS-Fixer configuration:

### Basic Configuration
- Config instance creation
- Config naming
- Risky rules are allowed
- Required rule sets are included (@PER-CS1x0, @DoctrineAnnotation)

### Array and List Syntax
- ✅ Array syntax is short: `[]` instead of `array()`
- ✅ List syntax is short: `[$a, $b]` instead of `list($a, $b)`
- ✅ Trailing commas in multiline arrays

### String and Concatenation
- ✅ Single quotes for simple strings
- ✅ One space around concatenation operator: `'hello' . 'world'`

### Type Declarations
- ✅ Nullable types use union syntax: `string|null` instead of `?string`
- ✅ No spaces in cast: `(int)$value` instead of `(int) $value`
- ✅ Type declaration spaces

### Code Style
- ✅ No yoda style comparisons: `$a == 5` instead of `5 == $a`
- ✅ Blank lines before statements (return, if, foreach, etc.)
- ✅ Declare equals without spaces: `declare(strict_types=1)`
- ✅ No unused imports
- ✅ Ordered imports (alphabetically)

### PHPUnit Specific
- ✅ Static method calls use `self::` instead of `static::`
- ✅ PHPUnit construct assertions

### Advanced Features
- ✅ Ternary to null coalescing: `$foo ?? 'default'` instead of `isset($foo) ? $foo : 'default'`
- ✅ Modernize strpos and type casting
- ✅ Custom rules can be added via `addRules()` method

## Continuous Integration

These tests should be run in your CI/CD pipeline to ensure code quality standards are maintained.

Example for GitHub Actions:

```yaml
- name: Run tests
  run: vendor/bin/phpunit
```

Example for GitLab CI:

```yaml
test:
  script:
    - composer install
    - vendor/bin/phpunit
```
