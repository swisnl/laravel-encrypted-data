<?php

namespace Swis\Laravel\Encrypted\Tests\Casts;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDate;
use Swis\Laravel\Encrypted\Tests\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\_mocks\Model;

class AsEncryptedDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    #[Test]
    public function getReturnsNullForNull(): void
    {
        $cast = AsEncryptedDate::castUsing([]);
        $model = new Model();

        $result = $cast->get($model, 'date', null, []);

        $this->assertNull($result);
    }

    #[Test]
    public function getAppliesStartOfDayClosure(): void
    {
        $cast = AsEncryptedDate::castUsing([]);
        $model = new Model();
        $date = '2024-07-02 15:30:45';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 00:00:00', $result->toDateTimeString());
    }

    #[Test]
    public function formatReturnsExpectedString(): void
    {
        $this->assertEquals(AsEncryptedDate::class.':Y-m-d', AsEncryptedDate::format('Y-m-d'));
    }
}
