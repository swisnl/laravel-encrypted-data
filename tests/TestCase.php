<?php

namespace Swis\Laravel\Encrypted\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Swis\Laravel\Encrypted\EncryptedDataServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EncryptedDataServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'JwedQwbFHOZamnyxwih0Pjc029U2KQpp');
    }
}
