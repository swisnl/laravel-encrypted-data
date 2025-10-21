<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Filesystem\FilesystemAdapter;

class EncryptedFilesystemAdapter extends FilesystemAdapter
{
    /**
     * Re-encrypt the contents of a file.
     */
    public function reEncrypt(string $path): bool
    {
        return (bool) $this->put($path, $this->get($path));
    }
}
