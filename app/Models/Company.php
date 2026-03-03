<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    public const PLAN_STARTER = 'starter';
    public const PLAN_GROWTH = 'growth';
    public const PLAN_PRO = 'pro';

    public const SUB_TRIAL = 'trial';
    public const SUB_ACTIVE = 'active';
    public const SUB_PAST_DUE = 'past_due';
    public const SUB_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'address',
        'timezone',
        'attendance_schedule_start',
        'attendance_schedule_end',
        'attendance_break_minutes',
        'attendance_grace_minutes',
        'is_active',
        'is_demo',

        // Billing/subscription (manual)
        'plan_name',
        'max_employees',
        'subscription_status',
        'trial_ends_at',
        'next_billing_at',
        'last_payment_at',
        'grace_days',
        'billing_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_demo' => 'boolean',
        'attendance_break_minutes' => 'integer',
        'attendance_grace_minutes' => 'integer',

        'max_employees' => 'integer',
        'grace_days' => 'integer',
        'trial_ends_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'last_payment_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
