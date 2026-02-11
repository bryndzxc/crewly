<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ActivityLogService extends Service
{
    public function log(string $action, Model $subject, array $properties = [], ?string $description = null): ActivityLog
    {
        $request = request();

        $description = $description ?? $this->defaultDescription($action, $subject);

        return ActivityLog::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ]);
    }

    public function logModelUpdated(
        Model $subject,
        array $before,
        array $trackedFields,
        array $properties = [],
        ?string $description = null
    ): ActivityLog {
        $changed = Arr::only($subject->getChanges(), $trackedFields);
        $changes = [];

        foreach ($changed as $field => $to) {
            $changes[$field] = ['from' => $before[$field] ?? null, 'to' => $to];
        }

        $properties = array_merge($properties, [
            'changes' => $changes,
        ]);

        return $this->log('updated', $subject, $properties, $description);
    }

    private function defaultDescription(string $action, Model $subject): string
    {
        $subjectLabel = class_basename($subject);

        $actionLabel = strtolower(trim($action));
        if (!in_array($actionLabel, ['created', 'updated', 'deleted'], true)) {
            $actionLabel = $actionLabel !== '' ? $actionLabel : 'performed';
        }

        return sprintf('%s has been %s.', $subjectLabel, $actionLabel);
    }
}
