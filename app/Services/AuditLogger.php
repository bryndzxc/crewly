<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * @param  array<string,mixed>  $old
     * @param  array<string,mixed>  $new
     * @param  array<string,mixed>  $meta
     */
    public function log(
        string $action,
        ?Model $model = null,
        array $old = [],
        array $new = [],
        array $meta = [],
        ?string $description = null
    ): void {
        $user = auth()->user();

        $request = request();
        $autoMeta = [];
        try {
            if ($request) {
                $autoMeta = [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'route' => optional($request->route())->getName(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ];
            }
        } catch (\Throwable $e) {
            // No request context (e.g. jobs/CLI).
        }

        $actorName = null;
        if ($user) {
            $actorName = $user->name ?? $user->email ?? (string) $user->id;
        }

        $oldSanitized = $this->sanitizeValues($old, $model);
        $newSanitized = $this->sanitizeValues($new, $model);
        $metaSanitized = $this->sanitizeValues(array_merge($autoMeta, $meta), null);

        AuditLog::query()->create([
            'user_id' => $user?->id,
            'actor_name' => $actorName,
            'action' => $action,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model ? $model->getKey() : null,
            'description' => $description,
            'old_values' => empty($oldSanitized) ? null : $oldSanitized,
            'new_values' => empty($newSanitized) ? null : $newSanitized,
            'metadata' => empty($metaSanitized) ? null : $metaSanitized,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string,mixed>  $values
     * @return array<string,mixed>
     */
    private function sanitizeValues(array $values, ?Model $model): array
    {
        if (empty($values)) return [];

        $flat = Arr::dot($values);

        $casts = [];
        if ($model) {
            try {
                $casts = method_exists($model, 'getCasts') ? $model->getCasts() : [];
            } catch (\Throwable $e) {
                $casts = [];
            }
        }

        $redactKeys = [
            'password',
            'current_password',
            'password_confirmation',
            'remember_token',
            'token',
            'api_token',
            'access_token',
            'refresh_token',
            'secret',
            'two_factor_recovery_codes',
            'two_factor_secret',
        ];

        $fileLikeKeys = [
            'file',
            'file_content',
            'content',
            'contents',
            'binary',
            'data',
        ];

        foreach ($flat as $key => $value) {
            $leaf = Str::lower(Str::afterLast($key, '.'));

            if (in_array($leaf, $redactKeys, true) || Str::contains($leaf, ['password', 'token', 'secret'])) {
                $flat[$key] = '[REDACTED]';
                continue;
            }

            if (in_array($leaf, $fileLikeKeys, true)) {
                if ($value instanceof UploadedFile) {
                    $flat[$key] = [
                        'original_name' => $value->getClientOriginalName(),
                        'mime' => $value->getClientMimeType(),
                        'size' => $value->getSize(),
                    ];
                } else {
                    $flat[$key] = '[FILE]';
                }
                continue;
            }

            if ($model) {
                $topKey = Str::before($key, '.');
                $cast = $casts[$topKey] ?? null;
                if (is_string($cast) && Str::startsWith($cast, 'encrypted')) {
                    $flat[$key] = '[ENCRYPTED]';
                    continue;
                }
            }

            if (is_string($value)) {
                // Avoid storing very large blobs accidentally.
                if (Str::length($value) > 2000) {
                    $flat[$key] = Str::substr($value, 0, 2000) . '…';
                }
            }
        }

        return Arr::undot($flat);
    }
}
