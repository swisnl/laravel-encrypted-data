<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class AsEncryptedImmutableDateTime implements Castable
{
    /**
     * @param string[] $arguments
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new EncryptedDateTime($arguments, fn (#[\SensitiveParameter] Carbon|CarbonImmutable|null $value) => $value?->toImmutable());
    }

    /**
     * Specify the format to use when the model is serialized to an array or JSON.
     */
    public static function format(string $format): string
    {
        return static::class.':'.$format;
    }
}
