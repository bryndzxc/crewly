<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function ($model) {
            if (!array_key_exists('company_id', $model->getAttributes())) {
                // If the attribute isn't set at all, only attempt to auto-fill.
                $user = Auth::user();
                if ($user?->company_id) {
                    $model->setAttribute('company_id', $user->company_id);
                }

                return;
            }

            // If present but empty, also fill.
            $current = $model->getAttribute('company_id');
            if ($current === null || $current === '') {
                $user = Auth::user();
                if ($user?->company_id) {
                    $model->setAttribute('company_id', $user->company_id);
                }
            }
        });
    }

    public static function withoutCompanyScope(): Builder
    {
        return static::query()->withoutGlobalScope(CompanyScope::class);
    }
}
