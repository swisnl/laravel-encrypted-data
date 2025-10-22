<?php

namespace Swis\Laravel\Encrypted\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Ftp\FtpAdapter;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\Tests\TestCase;

final class FilesystemTest extends TestCase
{
    protected function hasEncryptedInlineDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'encrypted', 'disk' => ['driver' => 'local', 'root' => dirname(__DIR__).'/_files/']]);
    }

    protected function hasEncryptedReferencedDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.other', ['driver' => 'local', 'root' => dirname(__DIR__).'/_files/']);
        $app['config']->set('filesystems.disks.local', ['driver' => 'encrypted', 'disk' => 'other']);
    }

    protected function hasEncryptedFtpDisk($app): void
    {
        $app['config']->set('filesystems.default', 'ftp');
        $app['config']->set('filesystems.disks.ftp', ['driver' => 'encrypted', 'disk' => ['driver' => 'ftp', 'host' => 'localhost']]);
    }

    protected function hasEncryptedDiskWithPrefix($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'encrypted', 'disk' => ['driver' => 'local', 'root' => dirname(__DIR__).'/_files/', 'prefix' => 'prefix']]);
    }

    protected function hasIncorrectEncryptedDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'encrypted']);
    }

    #[Test]
    #[DefineEnvironment('hasEncryptedInlineDisk')]
    public function itRegistersTheFilesystemDriverWithInlineDisk(): void
    {
        $contents = Storage::get('read.txt');

        $this->assertSame('YSvdOxSZ8pyTdDWeN8qI', $contents);
    }

    #[Test]
    #[DefineEnvironment('hasEncryptedReferencedDisk')]
    public function itRegistersTheFilesystemDriverWithReferencedDisk(): void
    {
        $contents = Storage::get('read.txt');

        $this->assertSame('YSvdOxSZ8pyTdDWeN8qI', $contents);
    }

    #[Test]
    #[DefineEnvironment('hasEncryptedFtpDisk')]
    public function itRegistersTheFilesystemDriverWithFtpDisk(): void
    {
        $filesystem = Storage::disk();

        $this->assertInstanceOf(FtpAdapter::class, $filesystem->getAdapter());
    }

    #[Test]
    #[DefineEnvironment('hasEncryptedDiskWithPrefix')]
    public function itRegistersTheFilesystemDriverWithPrefixedDisk(): void
    {
        $contents = Storage::get('read.txt');

        $this->assertSame('hi7OJgUQlfk00nd3jmM1', $contents);
    }

    #[Test]
    #[DefineEnvironment('hasIncorrectEncryptedDisk')]
    public function itFailsWhenDiskIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Encrypted disk is missing "disk" configuration option.');

        Storage::get('read.txt');
    }
}
