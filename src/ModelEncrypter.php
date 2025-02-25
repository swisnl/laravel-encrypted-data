<?php

namespace Swis\Laravel\Encrypted;

use Illuminate\Contracts\Encryption\Encrypter;

/**
 * @deprecated only use this when migrating from this package to Laravel's built-in encrypted casting
 */
class ModelEncrypter implements Encrypter
{
    private ?Encrypter $encrypter;

    public function __construct()
    {
        $this->encrypter = app('encrypted-data.encrypter');
    }

    public function encrypt($value, $serialize = true)
    {
        return $this->encrypter->encrypt($value, $serialize);
    }

    public function decrypt($payload, $unserialize = true)
    {
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
        return $this->encrypter->getAllKeys();
    }

    public function getPreviousKeys()
    {
        return $this->encrypter->getPreviousKeys();
    }
}
