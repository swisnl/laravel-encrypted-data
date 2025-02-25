<?php

namespace Swis\Laravel\Encrypted\Tests;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class FilesystemTest extends TestCase
{
    #[Test]
    public function itRegistersTheFilesystemDriver(): void
    {
        $contents = Storage::get('read.txt');

        $this->assertSame('YSvdOxSZ8pyTdDWeN8qI', $contents);
    }
}
