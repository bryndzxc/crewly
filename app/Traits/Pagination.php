<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait Pagination
{
    public function scopeSearchable(Builder $query, ?string $search = null, ?array $fields = null): Builder
    {
        $search = $search ?? (string) request()->query('q', request()->query('search', ''));
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $fields = $fields ?? ($this->searchable_fields ?? []);

        if (!is_array($fields) || count($fields) === 0) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($fields, $search) {
            foreach ($fields as $field) {
                if (!is_string($field) || $field === '') {
                    continue;
                }

                $subQuery->orWhere($field, 'like', '%' . $search . '%');
            }
        });
    }

    public function scopeSortable(
        Builder $query,
        ?string $orderBy = null,
        ?string $orderAction = null,
        ?array $allowedFields = null
    ): Builder {
        $orderBy = $orderBy ?? (string) request()->query('order_by', '');
        $orderAction = $orderAction ?? (string) request()->query('order_action', '');

        $orderBy = trim($orderBy);
        $orderAction = strtolower(trim($orderAction));

        $allowedFields = $allowedFields
            ?? (property_exists($this, 'sortable_fields') ? (array) $this->sortable_fields : null)
            ?? array_values(array_unique(array_filter(array_merge([
                $this->getKeyName(),
                'created_at',
                'updated_at',
            ], $this->getFillable()))));

        $direction = in_array($orderAction, ['asc', 'desc'], true) ? $orderAction : null;

        if ($orderBy !== '' && $direction) {
            if (!preg_match('/^[A-Za-z0-9_\.]+$/', $orderBy)) {
                $orderBy = '';
            }

            if ($orderBy !== '') {
                $table = $this->getTable();

                if (str_contains($orderBy, '.')) {
                    [$maybeTable, $column] = explode('.', $orderBy, 2);

                    if ($maybeTable !== $table) {
                        $orderBy = '';
                    } else {
                        $orderBy = $column;
                    }
                }

                if ($orderBy !== '' && in_array($orderBy, $allowedFields, true)) {
                    return $query->orderBy($this->getTable() . '.' . $orderBy, $direction);
                }
            }
        }

        return $query->orderBy($this->getTable() . '.' . $this->getKeyName(), 'desc');
    }

    public function scopePagination(Builder $query, ?int $perPage = null, array $columns = ['*']): LengthAwarePaginator
    {
        $perPage = $perPage ?? (int) request()->query('per_page', request()->query('row_per_page', 10));

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, $columns);
        $paginator->withQueryString();

        return $paginator;
    }
}
