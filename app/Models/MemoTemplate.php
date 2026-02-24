<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'body_html',
        'is_active',
        'is_system',
        'created_by_user_id',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'created_by_user_id' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }
}
