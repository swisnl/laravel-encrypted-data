<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Swis\Flysystem\Encrypted\EncryptedFilesystemAdapter;
use Swis\Laravel\Encrypted\Commands\ReEncryptModels;

class EncryptedDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerEncrypter();
    }

    protected function registerEncrypter(): void
    {
        $this->app->alias('encrypter', 'encrypted-data.encrypter');
    }

    public function boot(): void
    {
        $this->setupStorageDriver();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReEncryptModels::class,
            ]);
        }
    }

    protected function setupStorageDriver(): void
    {
        Storage::extend(
            'local-encrypted',
            function (Application $app, array $config) {
                $visibility = PortableVisibilityConverter::fromArray(
                    $config['permissions'] ?? [],
                    $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
                );

                $links = ($config['links'] ?? null) === 'skip'
                    ? LocalFilesystemAdapter::SKIP_LINKS
                    : LocalFilesystemAdapter::DISALLOW_LINKS;

                $adapter = new EncryptedFilesystemAdapter(
                    new LocalFilesystemAdapter(
                        $config['root'],
                        $visibility,
                        $config['lock'] ?? LOCK_EX,
                        $links
                    ),
                    $app->make('encrypted-data.encrypter')
                );

                $driver = new Filesystem(
                    $adapter,
                    Arr::only($config, [
                        'directory_visibility',
                        'disable_asserts',
                        'temporary_url',
                        'url',
                        'visibility',
                    ])
                );

                return new FilesystemAdapter($driver, $adapter, $config);
            }
        );
    }
}
