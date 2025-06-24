<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
        ];
    }
}
