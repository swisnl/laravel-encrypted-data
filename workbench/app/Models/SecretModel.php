<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Swis\Laravel\Encrypted\Casts\AsEncryptedBoolean;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDate;
use Swis\Laravel\Encrypted\Casts\AsEncryptedDateTime;
use Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDate;
use Swis\Laravel\Encrypted\Casts\AsEncryptedImmutableDateTime;

class SecretModel extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'encrypted_string' => 'encrypted',
            'encrypted_boolean' => AsEncryptedBoolean::class,
            'encrypted_date' => AsEncryptedDate::class,
            'encrypted_datetime' => AsEncryptedDateTime::class,
            'encrypted_immutable_date' => AsEncryptedImmutableDate::class,
            'encrypted_immutable_datetime' => AsEncryptedImmutableDateTime::class,

            'plain_boolean' => 'boolean',
            'plain_date' => 'date',
            'plain_datetime' => 'datetime',
        ];
    }
}
