<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'department_id',
        'employee_code',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'email_hash',
        'mobile_number',
        'mobile_number_hash',
        'first_name_bi',
        'last_name_bi',
        'first_name_prefix_bi',
        'last_name_prefix_bi',
        'photo_path',
        'photo_original_name',
        'photo_mime_type',
        'photo_size',
        'photo_is_encrypted',
        'photo_encryption_algo',
        'photo_encryption_iv',
        'photo_encryption_tag',
        'photo_key_version',
        'status',
        'position_title',
        'date_hired',
        'regularization_date',
        'employment_type',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'email_hash',
        'mobile_number_hash',
        'first_name_bi',
        'last_name_bi',
        'first_name_prefix_bi',
        'last_name_prefix_bi',
        'photo_path',
        'photo_original_name',
        'photo_is_encrypted',
        'photo_encryption_algo',
        'photo_encryption_iv',
        'photo_encryption_tag',
        'photo_key_version',
    ];

    protected $appends = [
        'has_photo',
        'photo_url',
    ];

    protected array $searchable_fields = [
        'employee_code',
        'status',
    ];

    protected array $sortable_fields = [
        'employee_id',
        'employee_code',
        'department_id',
        'status',
        'employment_type',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'first_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'last_name' => 'encrypted',
        'suffix' => 'encrypted',
        'email' => 'encrypted',
        'mobile_number' => 'encrypted',
        'position_title' => 'encrypted',
        'notes' => 'encrypted',
        'date_hired' => 'date:Y-m-d',
        'regularization_date' => 'date:Y-m-d',
        'first_name_prefix_bi' => 'array',
        'last_name_prefix_bi' => 'array',
    ];

    public function getHasPhotoAttribute(): bool
    {
        return (string) ($this->attributes['photo_path'] ?? '') !== '';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->has_photo || !$this->getKey()) {
            return null;
        }

        // Use a relative URL so photos still load even if APP_URL differs
        // from the actual host being used (e.g., Laragon domain).
        return route('employees.photo', $this->getKey(), false);
    }

    public function scopeSearchable(Builder $query, ?string $search = null, ?array $fields = null): Builder
    {
        $search = $search ?? (string) request()->query('q', request()->query('search', ''));
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $tokens = preg_split('/\s+/', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $query->where(function (Builder $sub) use ($search, $tokens) {
            $sub->where('employee_code', 'like', '%' . $search . '%');

            if (count($tokens) > 0) {
                $sub->orWhere(function (Builder $nameQ) use ($tokens) {
                    foreach ($tokens as $token) {
                        $token = trim((string) $token);
                        if ($token === '') {
                            continue;
                        }

                        $hash = hash_hmac('sha256', $token, (string) config('app.key', ''));

                        $nameQ->where(function (Builder $tq) use ($hash) {
                            $tq->whereJsonContains('first_name_prefix_bi', $hash)
                                ->orWhereJsonContains('last_name_prefix_bi', $hash);
                        });
                    }
                });
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id', 'employee_id');
    }
}
