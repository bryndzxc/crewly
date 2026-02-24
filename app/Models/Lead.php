<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'full_name',
        'company_name',
        'email',
        'phone',
        'company_size',
        'message',
        'source_page',
    ];
}
