# Changelog

All notable changes to `laravel-encrypted-data` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased]

- Nothing.

## [3.0.0-beta] - 2026-03-19

Please see [UPGRADING](UPGRADING.md) for details on how to upgrade.

### Added

- Added command to re-encrypt models.
- Added custom casts for encrypted booleans, date(time)s, floats and integers.
- Added command to re-encrypt files.
- Added support for Laravel 13.

### Changed

- The `EncryptedModel` class has been removed in favor of Laravel's built-in encrypted casting.
- The `local-encrypted` filesystem driver has been renamed to `encrypted` and now wraps another filesystem. This makes it possible to encrypt files stored on remote filesystems.
- Bumped minimum Laravel version to 12.
- Bumped minimum PHP version to 8.2.

## [2.3.0] - 2025-02-25

### Added

- Added support for Laravel 12.

### Changed

- Bumped minimum Laravel version to 10.
- Bumped minimum PHP version to 8.1.

## [2.2.0] - 2024-10-24

### Added

- Added support for Laravel 11.

## [2.1.0] - 2023-02-16

### Added

- Added support for Laravel 10.

## [2.0.0] - 2022-02-10

### Changed
- Bumped minimum Laravel version to 9.
