<?php

namespace Swis\Laravel\Encrypted\Tests\Casts;

use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedBoolean;
use Swis\Laravel\Encrypted\Tests\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\_mocks\Model;

class AsEncryptedBooleanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    public function testGetReturnsNullForNull(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'flag', null, []);

        $this->assertNull($result);
    }

    public function testGetReturnsBoolean(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedFalse = Model::$encrypter->encrypt('0', false);

        $this->assertTrue($cast->get($model, 'flag', $encryptedTrue, []));
        $this->assertFalse($cast->get($model, 'flag', $encryptedFalse, []));
    }

    public function testSetReturnsNullForNull(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $result = $cast->set($model, 'flag', null, []);

        $this->assertNull($result);
    }

    public function testSetReturnsEncryptedString(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encrypted = $cast->set($model, 'flag', true, []);
        $this->assertEquals(Model::$encrypter->encrypt('1', false), $encrypted);

        $encrypted = $cast->set($model, 'flag', false, []);
        $this->assertEquals(Model::$encrypter->encrypt('0', false), $encrypted);
    }

    public function testCompareReturnsTrueForSameDecryptedValue(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedTrue2 = Model::$encrypter->encrypt('1', false);

        $this->assertTrue($cast->compare($model, 'flag', $encryptedTrue, $encryptedTrue2));
    }

    public function testCompareReturnsFalseForDifferentDecryptedValue(): void
    {
        $cast = AsEncryptedBoolean::castUsing([]);
        $model = new Model();

        $encryptedTrue = Model::$encrypter->encrypt('1', false);
        $encryptedFalse = Model::$encrypter->encrypt('0', false);

        $this->assertFalse($cast->compare($model, 'flag', $encryptedTrue, $encryptedFalse));
    }

    public function testCompareReturnsFalseIfPreviousKeysExist(): void
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
