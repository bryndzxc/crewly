<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'name',
        'code',
    ];

    protected array $searchable_fields = [
        'name',
        'code',
    ];
}
