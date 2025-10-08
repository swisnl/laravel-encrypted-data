<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\_mocks;

class DummyEncrypter
{
    public array $previousKeys = [];

    public function decrypt($value, $serialize)
    {
        // For testing, just return the value as-is
        return $value;
    }

    public function encrypt($value, $serialize)
    {
        // For testing, just return the value as-is
        return $value;
    }

    public function getPreviousKeys(): array
    {
        return $this->previousKeys;
    }

    public function setPreviousKeys(array $previousKeys): void
    {
        $this->previousKeys = $previousKeys;
    }
}
