<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_demo' => 'boolean',
        'attendance_break_minutes' => 'integer',
        'attendance_grace_minutes' => 'integer',
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
