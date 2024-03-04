<?php

namespace Swis\Laravel\Encrypted\Tests;

use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\ModelEncrypter;

class ModelEncrypterTest extends TestCase
{
    private EncrypterContract&MockObject $mockEncrypter;
    private ModelEncrypter $modelEncrypter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockEncrypter = $this->createMock(EncrypterContract::class);
        $this->modelEncrypter = new ModelEncrypter($this->mockEncrypter, 'dummy-previous-key');
    }

    public function testEncryptDelegatesToEncrypter(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('encrypt')
            ->with('foo', true)
            ->willReturn('encrypted-foo');

        $result = $this->modelEncrypter->encrypt('foo', true);

        $this->assertEquals('encrypted-foo', $result);
    }

    public function testDecryptWithUnserializeTrue(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('decrypt')
            ->with('payload')
            ->willReturn('bar');

        $result = $this->modelEncrypter->decrypt('payload', true);

        $this->assertEquals('bar', $result);
    }

    public function testDecryptWithUnserializeFalseAndSerializedValue(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('decrypt')
            ->with('payload', false)
            ->willReturn('a:1:{i:0;s:3:"baz";}');

        $result = $this->modelEncrypter->decrypt('payload', false);

        $this->assertEquals(['baz'], $result);
    }

    public function testDecryptWithUnserializeFalseAndNonSerializedValue(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('decrypt')
            ->with('payload', false)
            ->willReturn('not-serialized');

        $result = $this->modelEncrypter->decrypt('payload', false);

        $this->assertEquals('not-serialized', $result);
    }

    public function testDecryptWithUnserializeFalseAndSerializedFalseValue(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('decrypt')
            ->with('payload', false)
            ->willReturn('b:0;');

        $result = $this->modelEncrypter->decrypt('payload', false);

        $this->assertFalse($result);
    }

    public function testGetKeyDelegates(): void
    {
        $this->mockEncrypter->expects($this->once())
            ->method('getKey')
            ->willReturn('key123');

        $this->assertEquals('key123', $this->modelEncrypter->getKey());
    }

    public function testGetPreviousKeysMergesWithDummy(): void
    {
        if (version_compare(Application::VERSION, '11.0.0', '<')) {
            $this->markTestSkipped('The test requires Laravel 11 or higher to run.');
        }

        $this->mockEncrypter->expects($this->once())
            ->method('getPreviousKeys')
            ->willReturn(['prev1', 'prev2']);

        $result = $this->modelEncrypter->getPreviousKeys();

        $this->assertCount(3, $result); // includes dummy
        $this->assertContains('prev1', $result);
        $this->assertContains('prev2', $result);
        $this->assertContains('dummy-previous-key', $result);
    }

    public function testGetAllKeysMergesCurrentAndPrevious(): void
    {
        if (version_compare(Application::VERSION, '11.0.0', '<')) {
            $this->markTestSkipped('The test requires Laravel 11 or higher to run.');
        }

        $this->mockEncrypter->expects($this->once())
            ->method('getKey')
            ->willReturn('key123');
        $this->mockEncrypter->expects($this->once())
            ->method('getPreviousKeys')
            ->willReturn(['prev1']);

        $result = $this->modelEncrypter->getAllKeys();

        $this->assertCount(3, $result); // includes dummy
        $this->assertContains('key123', $result);
        $this->assertContains('prev1', $result);
        $this->assertContains('dummy-previous-key', $result);
    }
}
