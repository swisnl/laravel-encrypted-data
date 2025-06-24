<?php

namespace Workbench\App\OtherSecretModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarModel extends Model
{
    use SoftDeletes;

    protected $table = 'secret_models';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'encrypted_string' => 'encrypted',
        ];
    }
}
