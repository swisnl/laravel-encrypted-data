<?php

namespace Swis\Laravel\Encrypted\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\Tests\TestCase;

final class FilesystemTest extends TestCase
{
    protected function usesEncryptedDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'local-encrypted', 'root' => dirname(__DIR__).'/_files/']);
    }

    #[Test]
    #[DefineEnvironment('usesEncryptedDisk')]
    public function itRegistersTheFilesystemDriver(): void
    {
        $contents = Storage::get('read.txt');

        $this->assertSame('YSvdOxSZ8pyTdDWeN8qI', $contents);
    }
}
