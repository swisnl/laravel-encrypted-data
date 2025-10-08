<?php

namespace Swis\Laravel\Encrypted\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Concerns\WithWorkbench;
use PHPUnit\Framework\Attributes\Test;
use Swis\Laravel\Encrypted\Tests\TestCase;
use Workbench\App\Models\SecretModel;
use Workbench\App\Models\SomeClass;
use Workbench\App\OtherSecretModels\BarModel;
use Workbench\App\OtherSecretModels\FooModel;

final class ReEncryptModelsTest extends TestCase
{
    use WithWorkbench;
    use RefreshDatabase;

    protected function hasPreviousKeys($app): void
    {
        $app['config']->set('app.previous_keys', [Str::random(32)]);
    }

    #[Test]
    public function commandRequiresPreviousKey(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models')
            ->expectsOutput('Not all models can be re-encrypted because a previous key has not been set up. Please set APP_PREVIOUS_KEYS first!')
            ->assertExitCode(1);
    }

    #[Test]
    public function failsWhenPathDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The path '.base_path('foo').' is not a directory.');
        $this->artisan('encrypted-data:re-encrypt:models', ['--path' => 'foo']);
    }

    #[Test]
    public function failsWhenNoModelsFounds(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--path' => base_path('bootstrap')])
            ->expectsOutput('No models found.')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function asksForConfirmation(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models')
            ->expectsConfirmation('The following models will be re-encrypted: '.PHP_EOL.SecretModel::class.PHP_EOL.'Do you want to continue?', 'no')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function acceptsRelativePath(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--path' => 'app/OtherSecretModels', '--force' => true])
            ->expectsOutputToContain(BarModel::class)
            ->expectsOutputToContain(FooModel::class)
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function acceptsAbsolutePath(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--path' => app_path('OtherSecretModels'), '--force' => true])
            ->expectsOutputToContain(BarModel::class)
            ->expectsOutputToContain(FooModel::class)
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function acceptsPathAndExceptSimultaneously(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--path' => app_path('OtherSecretModels'), '--except' => [BarModel::class], '--force' => true])
            ->expectsOutputToContain(FooModel::class)
            ->doesntExpectOutputToContain(BarModel::class)
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function outputsStatus(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->expectsOutput('Re-encrypting '.SecretModel::class.'...')
            ->expectsOutput('Re-encrypting done!')
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function modelOptionWorks(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--model' => [SecretModel::class], '--force' => true])
            ->assertExitCode(0);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function exceptOptionExcludesModel(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--except' => [SecretModel::class]])
            ->expectsOutput('No models found.')
            ->assertExitCode(1);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function exceptOptionCanBeOnlyAModelName(): void
    {
        $this->artisan('encrypted-data:re-encrypt:models', ['--except' => ['SecretModel']])
            ->expectsOutput('No models found.')
            ->assertExitCode(1);
    }

    #[Test]
    public function failsWhenModelAndExceptAreUsedTogether(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --models and --except options cannot be combined.');
        $this->artisan('encrypted-data:re-encrypt:models', [
            '--model' => [SecretModel::class],
            '--except' => [SecretModel::class],
        ]);
    }

    #[Test]
    public function failsWhenModelClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Model class Workbench\App\Models\Foo\Bar\Baz does not exist.');
        $this->artisan('encrypted-data:re-encrypt:models', ['--model' => ['Foo\Bar\Baz']]);
    }

    #[Test]
    public function failsWhenModelClassIsNotModel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class '.SomeClass::class.' is not a model.');
        $this->artisan('encrypted-data:re-encrypt:models', ['--model' => [SomeClass::class]]);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function itReEncryptsModels(): void
    {
        // Create a model with encrypted attributes
        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::create([
            'encrypted_string' => 'foo',
            'encrypted_boolean' => true,
            'encrypted_date' => '2024-01-01',
            'encrypted_datetime' => '2024-01-01 12:00:00',
            'encrypted_immutable_date' => '2024-01-01',
            'encrypted_immutable_datetime' => '2024-01-01 12:00:00',
        ]);

        $original = [
            'encrypted_string' => $model->getRawOriginal('encrypted_string'),
            'encrypted_boolean' => $model->getRawOriginal('encrypted_boolean'),
            'encrypted_date' => $model->getRawOriginal('encrypted_date'),
            'encrypted_datetime' => $model->getRawOriginal('encrypted_datetime'),
            'encrypted_immutable_date' => $model->getRawOriginal('encrypted_immutable_date'),
            'encrypted_immutable_datetime' => $model->getRawOriginal('encrypted_immutable_datetime'),
        ];

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        // Assert that the encrypted attributes have changed
        $this->assertNotEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));
        $this->assertNotEquals($original['encrypted_boolean'], $model->getRawOriginal('encrypted_boolean'));
        $this->assertNotEquals($original['encrypted_date'], $model->getRawOriginal('encrypted_date'));
        $this->assertNotEquals($original['encrypted_datetime'], $model->getRawOriginal('encrypted_datetime'));
        $this->assertNotEquals($original['encrypted_immutable_date'], $model->getRawOriginal('encrypted_immutable_date'));
        $this->assertNotEquals($original['encrypted_immutable_datetime'], $model->getRawOriginal('encrypted_immutable_datetime'));

        // Assert that the attributes themselves have not changed
        $this->assertEquals('foo', $model->getAttribute('encrypted_string'));
        $this->assertEquals(true, $model->getAttribute('encrypted_boolean'));
        $this->assertEquals('2024-01-01 00:00:00', $model->getAttribute('encrypted_date')->toDateTimeString());
        $this->assertEquals('2024-01-01 12:00:00', $model->getAttribute('encrypted_datetime')->toDateTimeString());
        $this->assertEquals('2024-01-01 00:00:00', $model->getAttribute('encrypted_immutable_date')->toDateTimeString());
        $this->assertEquals('2024-01-01 12:00:00', $model->getAttribute('encrypted_immutable_datetime')->toDateTimeString());
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function itReEncryptsModelsWithTrashed(): void
    {
        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::create([
            'encrypted_string' => 'foo',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ]);
        $model->delete();

        $original = [
            'encrypted_string' => $model->getRawOriginal('encrypted_string'),
        ];

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));

        $this->artisan('encrypted-data:re-encrypt:models', ['--with-trashed' => true, '--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertNotEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function itReEncryptsModelsWithoutTouchingTimestamps(): void
    {
        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::create([
            'encrypted_string' => 'foo',
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
        ]);

        $this->artisan('encrypted-data:re-encrypt:models', ['--no-touch' => true, '--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertEquals('2024-01-01 12:00:00', $model->getRawOriginal('updated_at'));

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertNotEquals('2024-01-01 12:00:00', $model->getRawOriginal('updated_at'));
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function itReEncryptsModelsQuietly(): void
    {
        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::create([
            'encrypted_string' => 'foo',
        ]);

        // Fake events
        \Event::fake();

        $this->artisan('encrypted-data:re-encrypt:models', ['--quietly' => true, '--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        // Assert no events were raised
        \Event::assertNotDispatched('eloquent.saving: '.SecretModel::class);
        \Event::assertNotDispatched('eloquent.saved: '.SecretModel::class);
        \Event::assertNotDispatched('eloquent.updating: '.SecretModel::class);
        \Event::assertNotDispatched('eloquent.updated: '.SecretModel::class);

        // Now test with events enabled
        \Event::fake();

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        // Assert events were raised
        \Event::assertDispatched('eloquent.saving: '.SecretModel::class);
        \Event::assertDispatched('eloquent.saved: '.SecretModel::class);
        \Event::assertDispatched('eloquent.updating: '.SecretModel::class);
        \Event::assertDispatched('eloquent.updated: '.SecretModel::class);
    }

    #[Test]
    #[DefineEnvironment('hasPreviousKeys')]
    public function castsOptionDeterminesWhichCastsToReEncrypt(): void
    {
        /** @var \Workbench\App\Models\SecretModel $model */
        $model = SecretModel::create([
            'encrypted_string' => 'foo',
            'encrypted_boolean' => true,
        ]);

        $original = [
            'encrypted_string' => $model->getRawOriginal('encrypted_string'),
            'encrypted_boolean' => $model->getRawOriginal('encrypted_boolean'),
        ];

        $this->artisan('encrypted-data:re-encrypt:models', ['--casts' => '/AsEncryptedBoolean/i', '--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));
        $this->assertNotEquals($original['encrypted_boolean'], $model->getRawOriginal('encrypted_boolean'));

        $this->artisan('encrypted-data:re-encrypt:models', ['--force' => true])
            ->assertExitCode(0);

        $model->refresh();

        $this->assertNotEquals($original['encrypted_string'], $model->getRawOriginal('encrypted_string'));
        $this->assertNotEquals($original['encrypted_boolean'], $model->getRawOriginal('encrypted_boolean'));
    }
}
