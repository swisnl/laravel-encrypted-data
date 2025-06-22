<?php

namespace Swis\Laravel\Encrypted\Tests\Unit;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\EncryptedModel;
use Swis\Laravel\Encrypted\Tests\TestCase;
use Swis\Laravel\Encrypted\Tests\Unit\_mocks\Builder;

final class EncryptedModelTest extends TestCase
{
    #[Test]
    public function itDecryptsRawAttributes(): void
    {
        // arrange
        $value = 'secret';
        $rawValue = 'eyJpdiI6ImJ1ZzZJeEd3bzVDeTJPZEtPYVRLR0E9PSIsInZhbHVlIjoiWDh2YjVjbmdkZDE2WDZOS0JtMHc4QT09IiwibWFjIjoiNDc1OWZhZjc2ZWVlY2U3ZDIwMDdkZTE4YzAxZDU4OTc1NzhmMWE3ZGUyNTI4NzQ0ZjBlNTE4OGUxMjE0NDI4OSJ9';
        $model = $this->getModelInstance();

        // act
        $model->setRawAttributes(['secret' => $rawValue]);

        // assert
        $this->assertEquals($value, $model->getAttribute('secret'));
    }

    #[Test]
    public function itDoesNotDecryptRawAttributesThatAreNotConfiguredAsSuch(): void
    {
        // arrange
        $value = 'foo-bar';
        $model = $this->getModelInstance();

        // act
        $model->setRawAttributes(['not_secret' => $value]);

        // assert
        $this->assertEquals($value, $model->getAttribute('not_secret'));
    }

    #[Test]
    public function itDoesNotDecryptRawAttributesThatAreNull(): void
    {
        // arrange
        $model = $this->getModelInstance();

        // act
        $model->setRawAttributes(['secret' => null]);

        // assert
        $this->assertNull($model->getAttribute('secret'));
    }

    #[Test]
    public function itDoesNotDecryptRawAttributesThatAreNotEncrypted(): void
    {
        // arrange
        $value = 'foo-bar';
        $model = $this->getModelInstance();

        // act
        $model->setRawAttributes(['secret' => $value]);

        // assert
        $this->assertEquals($value, $model->getAttribute('secret'));
    }

    #[Test]
    public function itEncryptsDataOnInsert(): void
    {
        // arrange
        $attributes = ['secret' => 'secret'];

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Illuminate\Database\Connection $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getName')
            ->willReturn('foo');

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Illuminate\Database\Eloquent\Builder $query */
        $query = $this->createMock(Builder::class);
        $query->method('getConnection')
            ->willReturn($connection);
        $query->expects($this->once())
            ->method('insertGetId')
            ->with($this->logicalNot($this->equalTo($attributes)), 'id')
            ->willReturn(1);
        $model = $this->getModelInstance($query);

        // act
        $model->fill($attributes)->save();

        // assert
        // expectations
    }

    #[Test]
    public function itEncryptsDataOnUpdate(): void
    {
        // arrange
        $attributes = ['secret' => 'secret'];

        /** @var \PHPUnit\Framework\MockObject\MockObject&\Illuminate\Database\Eloquent\Builder $query */
        $query = $this->createMock(Builder::class);
        $query->method('where')
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('update')
            ->with($this->logicalNot($this->equalTo($attributes)));
        $model = $this->getModelInstance($query, true);

        // act
        $model->update($attributes);

        // assert
        // expectations
    }

    private function getModelInstance(?Builder $query = null, bool $exists = false): EncryptedModel
    {
        return new class($query, $exists) extends EncryptedModel {
            protected $guarded = [];

            public $timestamps = false;

            protected $encrypted = ['secret'];

            private $query;

            public function __construct(?Builder $query = null, $exists = false)
            {
                parent::__construct([]);

                $this->query = $query;
                $this->exists = $exists;
            }

            public function newModelQuery()
            {
                return $this->query;
            }
        };
    }
}
