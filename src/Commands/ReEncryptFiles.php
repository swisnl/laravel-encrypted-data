<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Swis\Laravel\Encrypted\EncryptedFilesystemAdapter;
use Symfony\Component\Console\Output\OutputInterface;

class ReEncryptFiles extends Command
{
    protected $signature = 'encrypted-data:re-encrypt:files
                                {--disk=* : The disks whose files to re-encrypt}
                                {--dir=* : Directories (within your disk) to scan for files}
                                {--except=* : Files or directories (within your disk) that should be excluded}
                                {--force : Force the operation to run without confirmation}';

    protected $description = 'Re-encrypt files';

    public function handle(): int
    {
        if (empty(app('encrypted-data.encrypter')->getPreviousKeys())) {
            $this->error('Files can\'t be re-encrypted because a previous key has not been set up. Please set APP_PREVIOUS_KEYS first!');

            return self::FAILURE;
        }

        $disks = $this->disks();

        if ($disks->isEmpty()) {
            $this->warn('No disks found.');

            return self::FAILURE;
        }

        if (!$this->disksCanBeReEncrypted($disks)) {
            $this->error('Not all disks can be re-encrypted because they don\'t use encryption.');

            return self::FAILURE;
        }

        if ($this->option('force') === false && $this->confirm('The following disks will be re-encrypted: '.PHP_EOL.$disks->implode(PHP_EOL).PHP_EOL.'Do you want to continue?') === false) {
            return self::FAILURE;
        }

        foreach ($disks as $disk) {
            $this->line("Re-encrypting files in {$disk}...");
            $reEncryptedFiles = $this->reEncryptFiles($disk);

            if ($reEncryptedFiles->isEmpty()) {
                $this->warn("No files found in {$disk}.");

                return self::FAILURE;
            }
        }

        $this->info('Re-encrypting done!');

        return self::SUCCESS;
    }

    /**
     * @param string $disk
     */
    protected function reEncryptFiles(string $disk): Collection
    {
        $directories = collect($this->option('dir'));
        $except = collect($this->option('except'));

        // If no directories specified, default to root
        if ($directories->isEmpty()) {
            $directories->push('');
        }

        /** @var \Swis\Laravel\Encrypted\EncryptedFilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $directories
            ->flatMap(fn (string $directory): Collection => collect($filesystem->allFiles($directory)))
            ->when($except->isNotEmpty(), function (Collection $foundFiles) use ($except): Collection {
                return $foundFiles->reject(function (string $file) use ($except) {
                    foreach ($except as $ex) {
                        if ($file === $ex || str_starts_with($file, rtrim($ex, '/').'/')) {
                            return true;
                        }
                    }

                    return false;
                });
            })
            ->unique()
            ->each(function (string $file) use ($filesystem) {
                $this->line($file, verbosity: OutputInterface::VERBOSITY_VERBOSE);
                $filesystem->reEncrypt($file);
            });
    }

    /**
     * Determine the disks that should be re-encrypted.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function disks(): Collection
    {
        if (!empty($disks = $this->option('disk'))) {
            return collect($disks)
                ->each(function (string $disk): void {
                    if (!array_key_exists($disk, config('filesystems.disks', []))) {
                        throw new \InvalidArgumentException(sprintf('Disk %s does not exist.', $disk));
                    }
                });
        }

        return collect(config('filesystems.disks', []))
            ->keys()
            ->filter($this->diskCanBeReEncrypted(...));
    }

    /**
     * Check if the disks use encryption.
     *
     * @param \Illuminate\Support\Collection<int, string> $disks
     */
    protected function disksCanBeReEncrypted(Collection $disks): bool
    {
        return $disks->every($this->diskCanBeReEncrypted(...));
    }

    /**
     * Check if the disk uses encryption.
     */
    protected function diskCanBeReEncrypted(string $disk): bool
    {
        return rescue(static fn (): bool => Storage::disk($disk) instanceof EncryptedFilesystemAdapter, false, false);
    }
}
