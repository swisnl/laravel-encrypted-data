<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\Casts;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDateTime;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Model;

class AsEncryptedImmutableDateTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    #[Test]
    public function getReturnsNullForNull(): void
    {
        $cast = AsEncryptedImmutableDateTime::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'datetime', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function getReturnsCarbonImmutableInstance(): void
    {
        $cast = AsEncryptedImmutableDateTime::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'datetime', $date, []);

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertEquals('2024-07-02 15:30:45', $result->toDateTimeString());
    }

    #[Test]
    public function formatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedImmutableDateTime::class.':Y-m-d H:i:s', AsEncryptedImmutableDateTime::format('Y-m-d H:i:s'));
    }
}
