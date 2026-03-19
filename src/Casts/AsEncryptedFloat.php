<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

class AsEncryptedFloat implements Castable
{
    /**
     * @param string[] $arguments
     *
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<float, float>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes, ComparesCastableAttributes {
            /**
             * @param string|null          $value
             * @param array<string, mixed> $attributes
             */
            public function get(Model $model, string $key, mixed $value, array $attributes): ?float
            {
                if ($value === null) {
                    return null;
                }

                return $model->fromFloat($model::currentEncrypter()->decrypt($value, false));
            }

            /**
             * @param float|null           $value
             * @param array<string, mixed> $attributes
             */
            public function set(Model $model, string $key, #[\SensitiveParameter] mixed $value, array $attributes): ?string
            {
                if ($value === null) {
                    return null;
                }

                return $model::currentEncrypter()->encrypt((string) $value, false);
            }

            /**
             * @param string|null $firstValue
             * @param string|null $secondValue
             */
            public function compare(Model $model, string $key, mixed $firstValue, mixed $secondValue): bool
            {
                if (!empty($model::currentEncrypter()->getPreviousKeys())) {
                    return false;
                }

                return abs($this->get($model, $key, $firstValue, []) - $this->get($model, $key, $secondValue, [])) < PHP_FLOAT_EPSILON * 4;
            }
        };
    }
}
