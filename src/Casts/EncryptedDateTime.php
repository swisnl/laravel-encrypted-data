<?php

declare(strict_types=1);

namespace Swis\Laravel\Encrypted\Casts;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;

/**
 * @internal
 *
 * @implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Carbon|\Carbon\CarbonImmutable, string|\Illuminate\Support\Carbon|\Carbon\CarbonImmutable>
 */
class EncryptedDateTime implements CastsAttributes, SerializesCastableAttributes, ComparesCastableAttributes
{
    /**
     * @param array<int, string>                                                        $arguments
     * @param \Closure(Carbon|CarbonImmutable|null): (Carbon|CarbonImmutable|null)|null $modifier
     */
    public function __construct(protected array $arguments, protected ?\Closure $modifier = null)
    {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Carbon|CarbonImmutable|null
    {
        if ($value === null) {
            return null;
        }

        $value = ($model::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);

        return with($this->parse($model, $key, $value, $attributes), $this->modifier);
    }

    /**
     * @param \Illuminate\Support\Carbon|\Carbon\CarbonImmutable|string|null $value
     * @param array<string, mixed>                                           $attributes
     */
    public function set(Model $model, string $key, #[\SensitiveParameter] mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? $value : $value->format($model->getDateFormat());

        return ($model::$encrypter ?? Crypt::getFacadeRoot())->encrypt($value, false);
    }

    /**
     * @param string|null          $value
     * @param array<string, mixed> $attributes
     */
    public function serialize(Model $model, string $key, #[\SensitiveParameter] mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return !empty($this->arguments[0]) ? Date::parse($value)->format($this->arguments[0]) : $value;
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

        $firstValue = $this->get($model, $key, $firstValue, []);
        $secondValue = $this->get($model, $key, $secondValue, []);

        if ($firstValue === $secondValue) {
            return true;
        }

        if ($firstValue === null || $secondValue === null) {
            return false;
        }

        return $firstValue->equalTo($secondValue);
    }

    /**
     * @param string|int           $value
     * @param array<string, mixed> $attributes
     */
    protected function parse(Model $model, string $key, #[\SensitiveParameter] mixed $value, array $attributes): Carbon|CarbonImmutable|null
    {
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value, date_default_timezone_get());
        }

        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        try {
            return Date::createFromFormat($model->getDateFormat(), $value);
        } catch (\InvalidArgumentException) {
            return Date::parse($value);
        }
    }

    protected function isStandardDateFormat(#[\SensitiveParameter] string $value): bool
    {
        return (bool) preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }
}
