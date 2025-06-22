<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\Casts;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDateTime;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Model;

class AsEncryptedDateTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    public function testGetReturnsNullForNull(): void
    {
        $cast = AsEncryptedDateTime::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'datetime', null, []);

        $this->assertNull($result);
    }

    public function testGetReturnsCarbonInstance(): void
    {
        $cast = AsEncryptedDateTime::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'datetime', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 15:30:45', $result->toDateTimeString());
    }

    public function testFormatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedDateTime::class.':Y-m-d H:i:s', AsEncryptedDateTime::format('Y-m-d H:i:s'));
    }
}
