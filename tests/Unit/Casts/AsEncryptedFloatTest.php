<?php

namespace Casts;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedFloat;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Model;

class AsEncryptedFloatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    #[Test]
    public function getReturnsNullForNull(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'flag', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function getReturnsFloat(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $encryptedFloat = Model::$encrypter->encrypt('123.45', false);

        $this->assertIsFloat($cast->get($model, 'flag', $encryptedFloat, []));
    }

    #[Test]
    public function setReturnsNullForNull(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $result = $cast->set($model, 'flag', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function setReturnsEncryptedString(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $encrypted = $cast->set($model, 'flag', 123.45, []);
        $this->assertEquals(Model::$encrypter->encrypt('123.45', false), $encrypted);
    }

    #[Test]
    public function compareReturnsTrueForSameDecryptedValue(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $encrypted = Model::$encrypter->encrypt('123.45', false);
        $encrypted2 = Model::$encrypter->encrypt('123.45', false);

        $this->assertTrue($cast->compare($model, 'flag', $encrypted, $encrypted2));
    }

    #[Test]
    public function compareReturnsFalseForDifferentDecryptedValue(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        $encrypted = Model::$encrypter->encrypt('123.45', false);
        $encrypted2 = Model::$encrypter->encrypt('456.78', false);

        $this->assertFalse($cast->compare($model, 'flag', $encrypted, $encrypted2));
    }

    #[Test]
    public function compareReturnsFalseIfPreviousKeysExist(): void
    {
        $cast = AsEncryptedFloat::castUsing([]);
        $model = new Model();

        // Simulate previous keys
        /* @noinspection PhpUndefinedMethodInspection */
        $model::$encrypter->setPreviousKeys(['dummy-key']);

        $encrypted = Model::$encrypter->encrypt('123.45', false);

        $this->assertFalse($cast->compare($model, 'flag', $encrypted, $encrypted));
    }
}
