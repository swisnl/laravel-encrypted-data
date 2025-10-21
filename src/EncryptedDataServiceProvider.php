<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Swis\Flysystem\Encrypted\EncryptedFilesystemAdapter as EncryptedAdapter;
use Swis\Laravel\Encrypted\Commands\ReEncryptFiles;
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
                ReEncryptFiles::class,
                ReEncryptModels::class,
            ]);
        }
    }

    protected function setupStorageDriver(): void
    {
        Storage::extend(
            'encrypted',
            (function (Application $app, array $config) {
                /* @var \Illuminate\Filesystem\FilesystemManager $this */
                if (empty($config['disk'])) {
                    throw new \InvalidArgumentException('Encrypted disk is missing "disk" configuration option.');
                }

                $parent = $this->build(
                    is_string($config['disk']) ? $this->getConfig($config['disk']) : $config['disk']
                );

                $encryptedAdapter = new EncryptedAdapter(
                    $parent->getAdapter(),
                    $app->make('encrypted-data.encrypter')
                );

                return new EncryptedFilesystemAdapter(
                    $this->createFlysystem($encryptedAdapter, $parent->getConfig()),
                    $parent->getAdapter(),
                    $parent->getConfig()
                );
            })->bindTo(Storage::getFacadeRoot(), Storage::getFacadeRoot())
        );
    }
}
