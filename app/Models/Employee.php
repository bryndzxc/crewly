<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'company_id',
        'user_id',
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
        'company_id' => 'integer',
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

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            $key = (string) config('app.key', '');

            if ($employee->isDirty('email') || (string) ($employee->getAttribute('email_hash') ?? '') === '') {
                $email = (string) ($employee->getAttribute('email') ?? '');
                $normalized = strtolower(trim($email));
                $employee->setAttribute('email_hash', $normalized !== '' ? hash_hmac('sha256', $normalized, $key) : null);
            }

            if ($employee->isDirty('mobile_number') || ($employee->getAttribute('mobile_number_hash') === null && $employee->getAttribute('mobile_number') !== null)) {
                $mobile = $employee->getAttribute('mobile_number');
                if ($mobile === null) {
                    $employee->setAttribute('mobile_number_hash', null);
                } else {
                    $normalized = preg_replace('/\D+/', '', trim((string) $mobile)) ?? '';
                    $employee->setAttribute('mobile_number_hash', $normalized !== '' ? hash_hmac('sha256', $normalized, $key) : null);
                }
            }

            $firstNamePrefixes = $employee->getAttribute('first_name_prefix_bi');
            $firstNamePrefixesEmpty = is_array($firstNamePrefixes) && count($firstNamePrefixes) === 0;

            if ($employee->isDirty('first_name') || $firstNamePrefixes === null || $firstNamePrefixesEmpty) {
                $indexes = self::buildNameIndexes('first_name', (string) ($employee->getAttribute('first_name') ?? ''), $key);
                $employee->setAttribute('first_name_bi', $indexes['first_name_bi']);
                $employee->setAttribute('first_name_prefix_bi', $indexes['first_name_prefix_bi']);
            }

            $lastNamePrefixes = $employee->getAttribute('last_name_prefix_bi');
            $lastNamePrefixesEmpty = is_array($lastNamePrefixes) && count($lastNamePrefixes) === 0;

            if ($employee->isDirty('last_name') || $lastNamePrefixes === null || $lastNamePrefixesEmpty) {
                $indexes = self::buildNameIndexes('last_name', (string) ($employee->getAttribute('last_name') ?? ''), $key);
                $employee->setAttribute('last_name_bi', $indexes['last_name_bi']);
                $employee->setAttribute('last_name_prefix_bi', $indexes['last_name_prefix_bi']);
            }
        });
    }

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
                        $token = self::normalizeName((string) $token);
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(EmployeeNote::class, 'employee_id', 'employee_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(EmployeeIncident::class, 'employee_id', 'employee_id');
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildNameIndexes(string $field, string $value, string $key): array
    {
        $normalized = self::normalizeName($value);
        if ($normalized === '') {
            return [
                "{$field}_bi" => null,
                "{$field}_prefix_bi" => null,
            ];
        }

        $bi = hash_hmac('sha256', $normalized, $key);

        $parts = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $prefixes = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $maxLen = min(10, mb_strlen($part));
            for ($i = 1; $i <= $maxLen; $i++) {
                $prefix = mb_substr($part, 0, $i);
                $prefixes[] = hash_hmac('sha256', $prefix, $key);
            }
        }

        $prefixes = array_values(array_unique($prefixes));

        return [
            "{$field}_bi" => $bi,
            "{$field}_prefix_bi" => $prefixes,
        ];
    }

    private static function normalizeName(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = preg_replace('/\s+/', ' ', $v) ?? '';
        $v = preg_replace('/[^\p{L}\p{N}\s\-\']+/u', '', $v) ?? '';
        return trim($v);
    }
}
