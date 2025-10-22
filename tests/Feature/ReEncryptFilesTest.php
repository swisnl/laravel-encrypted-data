<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\Tests\TestCase;

final class ReEncryptFilesTest extends TestCase
{
    use WithWorkbench;
    use RefreshDatabase;

    protected Filesystem $filesystem;

    protected string $diskRoot;

    #[\Override]
    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->diskRoot = dirname(__DIR__, 2).'/workbench/storage/framework/testing/disks/local';

        $this->filesystem->cleanDirectory(dirname($this->diskRoot));

        parent::setUp();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->cleanDirectory(dirname($this->diskRoot));
    }

    protected function hasPreviousKeys($app): void
    {
        $app['config']->set('app.previous_keys', [Str::random(32)]);
    }

    protected function hasLocalDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'local', 'root' => $this->diskRoot]);

        $this->filesystem->ensureDirectoryExists($this->diskRoot);
    }

    protected function hasEncryptedDisk($app): void
    {
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local', ['driver' => 'encrypted', 'disk' => ['driver' => 'local', 'root' => $this->diskRoot]]);

        $this->filesystem->ensureDirectoryExists($this->diskRoot);
    }

    protected function hasExtraEncryptedDisk($app): void
    {
        $diskRoot = dirname($this->diskRoot).'/extra';
        $app['config']->set('filesystems.disks.extra', ['driver' => 'encrypted', 'disk' => ['driver' => 'local', 'root' => $diskRoot]]);

        $this->filesystem->ensureDirectoryExists($diskRoot);
    }

    #[Test]
    public function commandRequiresPreviousKey(): void
    {
        $this->artisan('encrypted-data:re-encrypt:files')
            ->expectsOutput('Files can\'t be re-encrypted because a previous key has not been set up. Please set APP_PREVIOUS_KEYS first!')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function failsWhenNoDisksFounds(): void
    {
        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true])
            ->expectsOutput('No disks found.')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function failsWhenDiskDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Disk some-disk does not exist.');
        $this->artisan('encrypted-data:re-encrypt:files', ['--disk' => ['some-disk'], '--force' => true]);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasLocalDisk')]
    public function failsWhenDiskDoesNotUseEncryption(): void
    {
        $this->artisan('encrypted-data:re-encrypt:files', ['--disk' => ['local'], '--force' => true])
            ->expectsOutput('Not all disks can be re-encrypted because they don\'t use encryption.')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function failsWhenThereAreNoFilesFound(): void
    {
        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true])
            ->expectsOutput('No files found in local.')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function asksForConfirmation(): void
    {
        $this->artisan('encrypted-data:re-encrypt:files')
            ->expectsConfirmation('The following disks will be re-encrypted: '.PHP_EOL.'local'.PHP_EOL.'Do you want to continue?', 'no')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function outputsStatus(): void
    {
        $this->filesystem->put($this->diskRoot.'/some-file.txt', encrypt('Some content'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true])
            ->expectsOutput('Re-encrypting files in local...')
            ->doesntExpectOutput('some-file.txt')
            ->expectsOutput('Re-encrypting done!')
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function outputsFilesInVerboseMode(): void
    {
        $this->filesystem->put($this->diskRoot.'/some-file.txt', encrypt('Some content'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true, '-v' => true])
            ->expectsOutput('Re-encrypting files in local...')
            ->expectsOutput('some-file.txt')
            ->expectsOutput('Re-encrypting done!')
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function acceptsDir(): void
    {
        $this->filesystem->put($path1 = $this->diskRoot.'/some-file.txt', $file1 = encrypt('Some content'));
        $this->filesystem->makeDirectory($this->diskRoot.'/some-dir');
        $this->filesystem->put($path2 = $this->diskRoot.'/some-dir/some-file.txt', $file2 = encrypt('Some content in some dir'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--dir' => ['some-dir'], '--force' => true])
            ->assertExitCode(0);

        $this->assertEquals($file1, $this->filesystem->get($path1));
        $this->assertNotEquals($file2, $this->filesystem->get($path2));

        $this->assertEquals('Some content', Storage::get('some-file.txt'));
        $this->assertEquals('Some content in some dir', Storage::get('some-dir/some-file.txt'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function exceptOptionExcludesDirectory(): void
    {
        $this->filesystem->put($path1 = $this->diskRoot.'/some-file.txt', $file1 = encrypt('Some content'));
        $this->filesystem->makeDirectory($this->diskRoot.'/some-dir');
        $this->filesystem->put($path2 = $this->diskRoot.'/some-dir/some-file.txt', $file2 = encrypt('Some content in some dir'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--except' => ['some-dir'], '--force' => true])
            ->assertExitCode(0);

        $this->assertNotEquals($file1, $this->filesystem->get($path1));
        $this->assertEquals($file2, $this->filesystem->get($path2));

        $this->assertEquals('Some content', Storage::get('some-file.txt'));
        $this->assertEquals('Some content in some dir', Storage::get('some-dir/some-file.txt'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function exceptOptionExcludesFile(): void
    {
        $this->filesystem->put($path1 = $this->diskRoot.'/some-file.txt', $file1 = encrypt('Some content'));
        $this->filesystem->makeDirectory($this->diskRoot.'/some-dir');
        $this->filesystem->put($path2 = $this->diskRoot.'/some-dir/some-file.txt', $file2 = encrypt('Some content in some dir'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--except' => ['some-file.txt'], '--force' => true])
            ->assertExitCode(0);

        $this->assertEquals($file1, $this->filesystem->get($path1));
        $this->assertNotEquals($file2, $this->filesystem->get($path2));

        $this->assertEquals('Some content', Storage::get('some-file.txt'));
        $this->assertEquals('Some content in some dir', Storage::get('some-dir/some-file.txt'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    public function itReEncryptsFiles(): void
    {
        $this->filesystem->put($path1 = $this->diskRoot.'/some-file.txt', $file1 = encrypt('Some content'));
        $this->filesystem->makeDirectory($this->diskRoot.'/some-dir');
        $this->filesystem->put($path2 = $this->diskRoot.'/some-dir/some-file.txt', $file2 = encrypt('Some content in some dir'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true])
            ->assertExitCode(0);

        $this->assertNotEquals($file1, $this->filesystem->get($path1));
        $this->assertNotEquals($file2, $this->filesystem->get($path2));

        $this->assertEquals('Some content', Storage::get('some-file.txt'));
        $this->assertEquals('Some content in some dir', Storage::get('some-dir/some-file.txt'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    #[DefineEnvironment('hasEncryptedDisk')]
    #[DefineEnvironment('hasExtraEncryptedDisk')]
    public function itReEncryptsMultipleDisks(): void
    {
        $this->filesystem->put($path1 = $this->diskRoot.'/some-file.txt', $file1 = encrypt('Some content'));
        $this->filesystem->put($path2 = dirname($this->diskRoot).'/extra/some-file.txt', $file2 = encrypt('Some content in another disk'));

        $this->artisan('encrypted-data:re-encrypt:files', ['--force' => true])
            ->expectsOutput('Re-encrypting files in local...')
            ->expectsOutput('Re-encrypting files in extra...')
            ->assertExitCode(0);

        $this->assertNotEquals($file1, $this->filesystem->get($path1));
        $this->assertNotEquals($file2, $this->filesystem->get($path2));

        $this->assertEquals('Some content', Storage::disk('local')->get('some-file.txt'));
        $this->assertEquals('Some content in another disk', Storage::disk('extra')->get('some-file.txt'));
    }
}
