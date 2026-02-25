<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmployeeRelationAttachment extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'attachable_type',
        'attachable_id',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
        'is_encrypted',
        'encryption_algo',
        'encryption_iv',
        'encryption_tag',
        'key_version',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'is_encrypted' => 'boolean',
        'file_size' => 'integer',
        'key_version' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }
}
