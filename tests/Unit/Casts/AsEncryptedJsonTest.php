<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\Casts;

use Illuminate\Support\Facades\Crypt;
use Orchestra\Testbench\TestCase;
use Swis\Laravel\Encrypted\Casts\AsEncryptedJson;
use Illuminate\Database\Eloquent\Model;

class AsEncryptedJsonTest extends TestCase
{
    public function test_it_encrypts_and_decrypts_json_data()
    {
        $cast = new AsEncryptedJson();
        $model = new class extends Model {};

        $original = ['theme' => 'dark', 'notifications' => true];
        $encrypted = $cast->set($model, 'settings', $original, []);
        $decrypted = $cast->get($model, 'settings', $encrypted, []);

        $this->assertEquals($original, $decrypted);
    }
}
