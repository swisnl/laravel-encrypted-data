<?php

namespace Swis\Laravel\Encrypted\Tests\Casts;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDate;
use Swis\Laravel\Encrypted\Tests\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\_mocks\Model;

class AsEncryptedImmutableDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    public function testGetReturnsNullForNull(): void
    {
        $cast = AsEncryptedImmutableDate::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'date', null, []);

        $this->assertNull($result);
    }

    public function testGetAppliesStartOfDayAndImmutable(): void
    {
        $cast = AsEncryptedImmutableDate::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertEquals('2024-07-02 00:00:00', $result->toDateTimeString());
    }

    public function testFormatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedImmutableDate::class.':Y-m-d', AsEncryptedImmutableDate::format('Y-m-d'));
    }
}
