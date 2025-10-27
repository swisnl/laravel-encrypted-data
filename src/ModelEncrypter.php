<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Contracts\Encryption\Encrypter;

/**
 * @deprecated only use this when upgrading from the package's EncryptedModel in 2.x to Laravel's built-in encrypted casting introduced in 3.x, this class will be removed in 4.x
 * @see ../MIGRATING.md for a step-by-step guide on how to migrate
 */
class ModelEncrypter implements Encrypter
{
    private ?Encrypter $encrypter;
    private array $previousKeys = [];

    public function __construct(?Encrypter $encrypter = null, ?string $previousKey = null)
    {
        $this->encrypter = $encrypter ?? app('encrypted-data.encrypter');
        // Generate a dummy previous key. This key is never actually used,
        // but then Laravel assumes the value should be re-encrypted.
        // This makes it easy to re-encrypt model attributes.
        $this->previousKeys[] = $previousKey ?? \Illuminate\Encryption\Encrypter::generateKey(config('app.cipher'));
    }

    public function encrypt(#[\SensitiveParameter] $value, $serialize = true)
    {
        return $this->encrypter->encrypt($value, $serialize);
    }

    public function decrypt($payload, $unserialize = true)
    {
        if ($unserialize) {
            return $this->encrypter->decrypt($payload);
        }

        $decrypted = $this->encrypter->decrypt($payload, false);

        $unserialized = @unserialize($decrypted);
        if ($unserialized === false && $decrypted !== 'b:0;') {
            return $decrypted;
        }

        return $unserialized;
    }

    public function getKey()
    {
        return $this->encrypter->getKey();
    }

    public function getAllKeys()
    {
        return [
            $this->getKey(),
            ...$this->getPreviousKeys(),
        ];
    }

    public function getPreviousKeys()
    {
        return [
            ...$this->encrypter->getPreviousKeys(),
            ...$this->previousKeys,
        ];
    }
}
