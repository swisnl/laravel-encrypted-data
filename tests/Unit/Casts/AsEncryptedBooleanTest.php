<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\Casts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedBoolean;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Model;

class AsEncryptedBooleanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    #[Test]
    public function getReturnsNullForNull(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'flag', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function getReturnsBoolean(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedFalse = Model::$encrypter->encrypt('0', false);

        $this->assertTrue($cast->get($model, 'flag', $encryptedTrue, []));
        $this->assertFalse($cast->get($model, 'flag', $encryptedFalse, []));
    }

    #[Test]
    public function setReturnsNullForNull(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $result = $cast->set($model, 'flag', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function setReturnsEncryptedString(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encrypted = $cast->set($model, 'flag', true, []);
        $this->assertEquals(Model::$encrypter->encrypt('1', false), $encrypted);

        $encrypted = $cast->set($model, 'flag', false, []);
        $this->assertEquals(Model::$encrypter->encrypt('0', false), $encrypted);
    }

    #[Test]
    public function compareReturnsTrueForSameDecryptedValue(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedTrue2 = Model::$encrypter->encrypt('1', false);

        $this->assertTrue($cast->compare($model, 'flag', $encryptedTrue, $encryptedTrue2));
    }

    #[Test]
    public function compareReturnsFalseForDifferentDecryptedValue(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedFalse = Model::$encrypter->encrypt('0', false);

        $this->assertFalse($cast->compare($model, 'flag', $encryptedTrue, $encryptedFalse));
    }

    #[Test]
    public function compareReturnsFalseIfPreviousKeysExist(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        // Simulate previous keys
        /* @noinspection PhpUndefinedMethodInspection */
        $model::$encrypter->setPreviousKeys(['dummy-key']);

        $encrypted = Model::$encrypter->encrypt('1', false);

        $this->assertFalse($cast->compare($model, 'flag', $encrypted, $encrypted));
    }
}
