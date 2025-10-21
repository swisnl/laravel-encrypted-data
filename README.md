# Laravel Utilities for Encrypted Data

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Buy us a tree][ico-treeware]][link-treeware]
[![Build Status][ico-github-actions]][link-github-actions]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
[![Made by SWIS][ico-swis]][link-swis]

This package contains several Laravel utilities to work with encrypted data.

## Install

Via Composer

```bash
composer require swisnl/laravel-encrypted-data
```

## Usage

### Eloquent casts

> [!WARNING]
> Older versions of this package needed a custom model class to encrypt data. This is now replaced with custom casts. Please see [MIGRATING](MIGRATING.md) for a step-by-step guide on how to migrate.
>

You can use the Eloquent casts provided by this package and everything will be encrypted/decrypted under the hood!

#### Boolean
Json
```php
protected $casts = [
    'settings' => \Swis\Laravel\Encrypted\Casts\AsEncryptedJson::class,
];



---

## ✅ **Summary of Changes**
| File | Purpose |
|------|----------|
| `src/Casts/AsEncryptedJson.php` | Adds new cast implementation |
| `tests/Unit/Casts/AsEncryptedJsonTest.php` | Adds test coverage |
| *(optional)* `README.md` | Adds short usage example |

---

## ✅ **Final Step Before PR**
Run these:
```bash
composer check-style
composer test

```php
protected $casts = [
    'boolean' => \Swis\Laravel\Encrypted\Casts\AsEncryptedBoolean::class,
];
```

#### Datetime

```php
protected $casts = [
    'date' => \Swis\Laravel\Encrypted\Casts\AsEncryptedDate::class,
    'datetime' => \Swis\Laravel\Encrypted\Casts\AsEncryptedDateTime::class,
    'immutable_date' => \Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDate::class,
    'immutable_datetime' => \Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDateTime::class,
    'date_with_custom_format' => \Swis\Laravel\Encrypted\Casts\AsEncryptedDate::format('Y-m-d'),
];
```

### Filesystem

Configure the storage driver in `config/filesystems.php`.

```php
'disks' => [
    'local' => [
        'driver' => 'local-encrypted',
        'root' => storage_path('app'),
    ],
],
```

You can now simply use the storage methods as usual and everything will be encrypted/decrypted under the hood!

### Commands

This package provides Artisan commands to help you re-encrypt your data after [rotating your encryption key](https://laravel.com/docs/12.x/encryption#gracefully-rotating-encryption-keys). You want to run these commands because Laravel only re-encrypts data when a value actually changes. This means that after rotating your encryption key, all existing data remains encrypted with the old key until it is updated. If your previous key is ever compromised, or you want to ensure all data uses the new key, you need to re-encrypt everything. These commands automate that process, making sure all your data is protected with the latest encryption key.

> [!IMPORTANT]
> Before running these commands, ensure you have rotated your encryption key and have set the `APP_PREVIOUS_KEYS` environment variable with your previous encryption key(s).

#### Re-encrypt models

Re-encrypts all model attributes that use encrypted casts.

```bash
php artisan encrypted-data:re-encrypt:models
```

Options:
* `--model=`: Specify one or more model class names to re-encrypt. Auto-detects models if not provided.
* `--except=`: Exclude one or more model class names from re-encryption.
* `--path=`: Path(s) to directories where models are located. Falls back to Models directory if not provided.
* `--casts=`: Regex to match casts that should be re-encrypted.
* `--chunk=`: Number of models to process per chunk.
* `--quietly`: Re-encrypt models without raising events.
* `--no-touch`: Do not update timestamps when saving.
* `--with-trashed`: Include soft-deleted models.
* `--force`: Run without confirmation.
* `--verbose`: Be more verbose about what the command is doing.

#### Re-encrypt files

Re-encrypts all files on encrypted disks.

```bash
php artisan encrypted-data:re-encrypt:files
```

Options:
* `--disk=`: Specify one or more disks to re-encrypt. Auto-detects disks if not provided.
* `--dir=`: Directories (within the disk) to scan for files. Defaults to root if not provided.
* `--except=`: Files or directories (within the disk) to exclude.
* `--force`: Run without confirmation.
* `--verbose`: Be more verbose about what the command is doing.

## Known issues/limitations

Due to the encryption, some issues/limitations apply:

1. Encrypted data is — depending on what you encrypt — roughly 30-40% bigger.

### Casts

1. You can't query or order columns that are encrypted in your SQL-statements, but you can query or sort the results using collection methods.

### Filesystem

1. You can't use the public disk as that will download the raw encrypted files, so using `Storage::url()` and `Storage::temporaryUrl()` does not make sense;
2. You can use streams with this disk, but internally we will always convert those to strings because the entire file contents need to be encrypted/decrypted at once.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email security@swis.nl instead of using the issue tracker.

## Credits

- [Jasper Zonneveld][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**][link-treeware] to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

## SWIS :heart: Open Source

[SWIS][link-swis] is a web agency from Leiden, the Netherlands. We love working with open source software. 

[ico-version]: https://img.shields.io/packagist/v/swisnl/laravel-encrypted-data.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-treeware]: https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen.svg?style=flat-square
[ico-github-actions]: https://img.shields.io/github/actions/workflow/status/swisnl/laravel-encrypted-data/tests.yml?label=tests&branch=master&style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/swisnl/laravel-encrypted-data.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/swisnl/laravel-encrypted-data.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/swisnl/laravel-encrypted-data.svg?style=flat-square
[ico-swis]: https://img.shields.io/badge/%F0%9F%9A%80-made%20by%20SWIS-%230737A9.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/swisnl/laravel-encrypted-data
[link-github-actions]: https://github.com/swisnl/laravel-encrypted-data/actions/workflows/tests.yml
[link-scrutinizer]: https://scrutinizer-ci.com/g/swisnl/laravel-encrypted-data/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/swisnl/laravel-encrypted-data
[link-downloads]: https://packagist.org/packages/swisnl/laravel-encrypted-data
[link-treeware]: https://plant.treeware.earth/swisnl/laravel-encrypted-data
[link-author]: https://github.com/swisnl
[link-contributors]: ../../contributors
[link-swis]: https://www.swis.nl
