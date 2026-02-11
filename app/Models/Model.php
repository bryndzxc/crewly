<?php

namespace App\Models;

use App\Traits\Pagination;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Model extends Eloquent
{
    use Pagination;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Allow mass assignment by default (override per-model if desired).
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<int, string>
     */
    protected array $searchable_fields = [];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
        'deleted_by',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function setSearchableFields(array $searchable_fields)
    {
        $this->searchable_fields = $searchable_fields;

        return $this;
    }

    public function scopeSelectedFields(Builder $query): Builder
    {
        return $query->select(["{$this->getTable()}.*"]);
    }

    public function getEncryptable(): mixed
    {
        return property_exists($this, 'encryptable') ? $this->encryptable : null;
    }
}
