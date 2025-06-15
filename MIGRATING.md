# Migrating swisnl/laravel-encrypted-data

## To Laravel Encrypted Casting
The main difference between this package and [Laravel Encrypted Casting](https://laravel.com/docs/eloquent-mutators#encrypted-casting) is that this package serializes the data before encrypting it, while Laravel Encrypted Casting encrypts the data directly. This means that the data is not compatible between the two packages. In order to migrate from this package to Laravel Encrypted Casting, you will need to decrypt the data and then re-encrypt it using Laravel Encrypted Casting. Here is a step-by-step guide on how to do this:

1. Make sure you're running on Laravel 12.18 or higher.
2. Remove the `Swis\Laravel\Encrypted\EncryptedModel` from your models and replace it with `Illuminate\Database\Eloquent\Model`:
```diff
- use Swis\Laravel\Encrypted\EncryptedModel
+ use Illuminate\Database\Eloquent\Model

- class YourEncryptedModel extends EncryptedModel
+ class YourEncryptedModel extends Model
```
3. Set up Encrypted Casting:
```diff
- protected $encrypted = [
-     'secret',
- ];
+ protected $casts = [
+     'secret' => 'encrypted',
+ ];
```
4. If you're using encrypted booleans or date(time)s, use the custom casts provided by this package:
```diff
- protected $encrypted = [
-     'secret_boolean',
-     'secret_datetime',
- ];
-
- protected $casts = [
-     'secret_boolean' => 'bool',
-     'secret_datetime' => 'datetime',
- ];
+ protected $casts = [
+     'secret_boolean' => \Swis\Laravel\Encrypted\Casts\AsEncryptedBoolean::class,
+     'secret_datetime' => \Swis\Laravel\Encrypted\Casts\AsEncryptedDateTime::class,
+ ];
```
5. If you're using other casts for encrypted attributes, or you need serialization support, you should create custom casts yourself, as this package does not provide casts for every situation. Please see [Custom Casts](https://laravel.com/docs/eloquent-mutators#custom-casts) for more information on how to create custom casts. You can use any of the casts provided by this package as a reference.
6. Set up our custom model encrypter in your `AppServiceProvider`:
```php
public function boot(): void
{
    $modelEncrypter = new \Swis\Laravel\Encrypted\ModelEncrypter();
    YourEncryptedModel::encryptUsing($modelEncrypter);
    // ... all your other models that used to extend \Swis\Laravel\Encrypted\EncryptedModel
}
```
This custom model encrypter is backward compatible with the old `EncryptedModel` and will handle the deserialization of the data before casts kick in. Data will **not** be serialized when re-encrypting, so it will be compatible with Laravel Encrypted Casting. This makes sure your application can keep running and the data is not lost during the migration process.
7. Run our re-encryption command:
```bash
php artisan encrypted-data:re-encrypt:models --quietly --no-touch
```
N.B. Use `--help` to see all available options and modify as needed!
8. Remove our custom model encrypter from your `AppServiceProvider` (step 6).
