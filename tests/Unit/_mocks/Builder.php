<?php

namespace Swis\Laravel\Encrypted\Tests\Unit\_mocks;

use Illuminate\Database\ConnectionInterface;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    public function insertGetId(array $values, $sequence = null): int
    {
        return $this->toBase()->insertGetId($values, $sequence);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->toBase()->getConnection();
    }
}
