<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AsEncryptedBoolean implements Castable
{
    /**
     * @param string[] $arguments
     *
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<bool, bool>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        /*
         * @todo Implement \Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes when Laravel <12 support is dropped.
         */
        return new class implements CastsAttributes {
            /**
             * @param string|null          $value
             * @param array<string, mixed> $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): ?bool
            {
                if ($value === null) {
                    return null;
                }

                return (bool) ($model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);
            }

            /**
             * @param bool|null            $value
             * @param array<string, mixed> $attributes
             */
            public function set(Model $model, string $key, #[\SensitiveParameter] mixed $value, array $attributes): ?string
            {
                if ($value === null) {
                    return null;
                }

                return ($model::$encrypter ?? Crypt::getFacadeRoot())->encrypt((string) (int) $value, false);
            }

            /**
             * @param string|null $firstValue
             * @param string|null $secondValue
             */
            public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
            {
                if (!empty(($model::$encrypter ?? Crypt::getFacadeRoot())->getPreviousKeys())) {
                    return false;
                }

                return $this->get($model, $key, $firstValue, []) === $this->get($model, $key, $secondValue, []);
            }
        };
    }
}
