<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class PlainModel extends Model
{
    protected function casts(): array
    {
        return [
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
        ];
    }
}
