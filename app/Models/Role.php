<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected array $searchable_fields = [
        'name',
        'key',
    ];

    protected $fillable = [
        'key',
        'name',
    ];
}
