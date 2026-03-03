<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    public const TYPE_DEMO = 'demo';
    public const TYPE_ACCESS = 'access';

    protected $fillable = [
        'full_name',
        'company_name',
        'email',
        'phone',
        'company_size',
        'message',
        'source_page',
        'lead_type',
        'employee_count_range',
        'requested_plan',
        'industry',
        'current_process',
        'biggest_pain',
        'status',
        'approved_at',
        'declined_at',
        'company_id',
        'user_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'company_id' => 'integer',
        'user_id' => 'integer',
    ];
}
