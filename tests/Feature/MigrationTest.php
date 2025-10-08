<?php

namespace Swis\Laravel\Encrypted\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\ModelEncrypter;
use Swis\Laravel\Encrypted\Tests\TestCase;
use Workbench\App\Models\SecretModel;

class MigrationTest extends TestCase
{
    use WithWorkbench;
    use RefreshDatabase;

    #[Test]
    public function itCanMigrateDataFromOldToNewFormat(): void
    {
        // Create a model with encrypted serialized attributes (old format)
        $original = [
            'encrypted_string' => encrypt('foo', true),
            'encrypted_boolean' => encrypt(true, true),
            'encrypted_date' => encrypt('2024-01-01', true),
            'encrypted_datetime' => encrypt('2024-01-01 12:00:00', true),
            'encrypted_immutable_date' => encrypt('2024-01-01', true),
            'encrypted_immutable_datetime' => encrypt('2024-01-01 12:00:00', true),
        ];

        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::make();
        $model->setRawAttributes($original);
        $model->save();

        SecretModel::encryptUsing(new ModelEncrypter());

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        SecretModel::encryptUsing(null);

        $model->refresh();

        // Assert that the encrypted attributes have changed
        $this->assertNotEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));
        $this->assertNotEquals($original['encrypted_boolean'], $model->getRawOriginal('encrypted_boolean'));
        $this->assertNotEquals($original['encrypted_date'], $model->getRawOriginal('encrypted_date'));
        $this->assertNotEquals($original['encrypted_datetime'], $model->getRawOriginal('encrypted_datetime'));
        $this->assertNotEquals($original['encrypted_immutable_date'], $model->getRawOriginal('encrypted_immutable_date'));
        $this->assertNotEquals($original['encrypted_immutable_datetime'], $model->getRawOriginal('encrypted_immutable_datetime'));

        // Assert that the attributes are now encrypted, but not serialized (new format) and have not changed
        $this->assertEquals('foo', decrypt($model->getRawOriginal('encrypted_string'), false));
        $this->assertEquals(true, decrypt($model->getRawOriginal('encrypted_boolean'), false));
        $this->assertEquals('2024-01-01 00:00:00', decrypt($model->getRawOriginal('encrypted_date'), false));
        $this->assertEquals('2024-01-01 12:00:00', decrypt($model->getRawOriginal('encrypted_datetime'), false));
        $this->assertEquals('2024-01-01 00:00:00', decrypt($model->getRawOriginal('encrypted_immutable_date'), false));
        $this->assertEquals('2024-01-01 12:00:00', decrypt($model->getRawOriginal('encrypted_immutable_datetime'), false));

        // Assert that the attributes can be accessed correctly using the casts
        $this->assertEquals('foo', $model->getAttribute('encrypted_string'));
        $this->assertEquals(true, $model->getAttribute('encrypted_boolean'));
        $this->assertEquals('2024-01-01 00:00:00', $model->getAttribute('encrypted_date')->toDateTimeString());
        $this->assertEquals('2024-01-01 12:00:00', $model->getAttribute('encrypted_datetime')->toDateTimeString());
        $this->assertEquals('2024-01-01 00:00:00', $model->getAttribute('encrypted_immutable_date')->toDateTimeString());
        $this->assertEquals('2024-01-01 12:00:00', $model->getAttribute('encrypted_immutable_datetime')->toDateTimeString());
    }
}
