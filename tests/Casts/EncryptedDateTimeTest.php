<?php

namespace Swis\Laravel\Encrypted\Tests\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;
use Swis\Laravel\Encrypted\Casts\EncryptedDateTime;
use Swis\Laravel\Encrypted\Tests\_mocks\DummyEncrypter;
use Swis\Laravel\Encrypted\Tests\_mocks\Model;

class EncryptedDateTimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::$encrypter = new DummyEncrypter();
    }

    public function testGetReturnsNullWhenValueIsNull(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $result = $cast->get($model, 'date', null, []);

        $this->assertNull($result);
    }

    public function testGetParsesNumericTimestamp(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = 1719923640; // 2024-07-02 12:34:00 UTC

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 12:34:00', $result->toDateTimeString());
    }

    public function testGetParsesStandardDateFormat(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = '2024-07-02';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 00:00:00', $result->toDateTimeString());
    }

    public function testGetParsesCustomFormat(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $model->setDateFormat('d/m/y H:i:s');
        $date = '02/07/24 15:30:00';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 15:30:00', $result->toDateTimeString());
    }

    public function testGetFallbacksToParseOnInvalidFormat(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = 'July 2, 2024 8:00pm';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024-07-02 20:00:00', $result->toDateTimeString());
    }

    public function testGetAppliesModifierIfSet(): void
    {
        $cast = new EncryptedDateTime([], fn ($value) => $value->addDay());
        $model = new Model();
        $date = '2024-07-02 00:00:00';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertEquals('2024-07-03 00:00:00', $result->toDateTimeString());
    }

    public function testGetReturnsImmutableWhenDateFacadeConfigured(): void
    {
        Date::use(CarbonImmutable::class);
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = '2024-07-02 12:34:00';

        $result = $cast->get($model, 'date', $date, []);

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertEquals('2024-07-02 12:34:00', $result->toDateTimeString());

        // Reset Date facade to default
        Date::use(DateFactory::DEFAULT_CLASS_NAME);
    }

    public function testSetReturnsNullWhenValueIsNull(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $result = $cast->set($model, 'date', null, []);

        $this->assertNull($result);
    }

    public function testSetEncryptsCarbonInstance(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = Carbon::create(2024, 7, 2, 12, 0, 0);

        $result = $cast->set($model, 'date', $date, []);

        $this->assertEquals('2024-07-02 12:00:00', $result);
    }

    public function testSetEncryptsString(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = '2024-07-02 12:00:00';

        $result = $cast->set($model, 'date', $date, []);

        $this->assertEquals($date, $result);
    }

    public function testSerializeReturnsNullWhenValueIsNull(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $result = $cast->serialize($model, 'date', null, []);

        $this->assertNull($result);
    }

    public function testSerializeFormatsValueUsingTheDefaultFormat(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();
        $date = Carbon::create(2024, 7, 2, 12, 0, 0)->toJSON();

        $result = $cast->serialize($model, 'date', $date, []);

        $this->assertEquals('2024-07-02T12:00:00.000000Z', $result);
    }

    public function testSerializeFormatsValueUsingTheProvidedFormat(): void
    {
        $cast = new EncryptedDateTime(['Y-m-d']);
        $model = new Model();
        $date = Carbon::create(2024, 7, 2, 12, 0, 0)->toJSON();

        $result = $cast->serialize($model, 'date', $date, []);

        $this->assertEquals('2024-07-02', $result);
    }

    public function testCompareReturnsTrueForEqualDates(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $date = '2024-07-02 12:00:00';
        $encrypted = $model::$encrypter->encrypt($date, false);

        $this->assertTrue($cast->compare($model, 'date', $encrypted, $encrypted));
    }

    public function testCompareReturnsFalseForDifferentDates(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $date1 = '2024-07-02 12:00:00';
        $date2 = '2024-07-03 12:00:00';
        $encrypted1 = $model::$encrypter->encrypt($date1, false);
        $encrypted2 = $model::$encrypter->encrypt($date2, false);

        $this->assertFalse($cast->compare($model, 'date', $encrypted1, $encrypted2));
    }

    public function testCompareReturnsFalseIfOriginalIsNull(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $date = '2024-07-02 12:00:00';
        $encrypted = $model::$encrypter->encrypt($date, false);

        $this->assertFalse($cast->compare($model, 'date', null, $encrypted));
    }

    public function testCompareReturnsFalseIfValueIsNull(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        $date = '2024-07-02 12:00:00';
        $encrypted = $model::$encrypter->encrypt($date, false);

        $this->assertFalse($cast->compare($model, 'date', $encrypted, null));
    }

    public function testCompareReturnsFalseIfPreviousKeysExist(): void
    {
        $cast = new EncryptedDateTime([]);
        $model = new Model();

        // Simulate previous keys
        /* @noinspection PhpUndefinedMethodInspection */
        $model::$encrypter->setPreviousKeys(['dummy-key']);

        $date = '2024-07-02 12:00:00';
        $encrypted = $model::$encrypter->encrypt($date, false);

        $this->assertFalse($cast->compare($model, 'date', $encrypted, $encrypted));
    }
}
