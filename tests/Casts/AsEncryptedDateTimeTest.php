<?php

namespace Swis\Laravel\Encrypted\Tests\Casts;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDateTime;
use Swis\Laravel\Encrypted\Tests\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\_mocks\Model;

class AsEncryptedDateTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    #[Test]
    public function getReturnsNullForNull(): void
    {
        $cast = AsEncryptedDateTime::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'datetime', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function getReturnsCarbonInstance(): void
    {
        $cast = AsEncryptedDateTime::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'datetime', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 15:30:45', $result->toDateTimeString());
    }

    #[Test]
    public function formatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedDateTime::class.':Y-m-d H:i:s', AsEncryptedDateTime::format('Y-m-d H:i:s'));
    }
}
