<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\Casts;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDate;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Model;

class AsEncryptedDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    public function testGetReturnsNullForNull(): void
    {
        $cast = AsEncryptedDate::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'date', null, []);

        $this->assertNull($result);
    }

    public function testGetAppliesStartOfDayClosure(): void
    {
        $cast = AsEncryptedDate::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 00:00:00', $result->toDateTimeString());
    }

    public function testFormatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedDate::class.':Y-m-d', AsEncryptedDate::format('Y-m-d'));
    }
}
