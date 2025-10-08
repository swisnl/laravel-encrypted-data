<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsEncryptedDateTime implements Castable
{
    /**
     * @param string[] $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new EncryptedDateTime($arguments);
    }

    /**
     * Specify the format to use when the model is serialized to an array or JSON.
     */
    public static function format(string $format): string
    {
        return static::class.':'.$format;
    }
}
