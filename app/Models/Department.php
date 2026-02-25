<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'company_id',
        'name',
        'code',
    ];

    protected $casts = [
        'company_id' => 'integer',
    ];

    protected array $searchable_fields = [
        'name',
        'code',
    ];
}
