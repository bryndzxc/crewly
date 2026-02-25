<?php

namespace App\Services;

use App\DTO\AuditLogFilterData;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService extends Service
{
    /**
     * @return array{filters:array<string,mixed>,logs:mixed,users:array<int,array{id:int,name:string,email:string|null}>,modules:array<int,array{value:string,label:string}>}
     */
    public function index(Request $request): array
    {
        $filters = AuditLogFilterData::fromRequest($request);

        $query = AuditLog::query()->with('user:id,name,email');

        if ($filters->from !== '') {
            $query->where('created_at', '>=', $filters->from . ' 00:00:00');
        }
        if ($filters->to !== '') {
            $query->where('created_at', '<=', $filters->to . ' 23:59:59');
        }
        if ($filters->userId) {
            $query->where('user_id', $filters->userId);
        }
        if ($filters->action !== '') {
            $query->where('action', 'like', '%' . $filters->action . '%');
        }
        if ($filters->module !== '') {
            $query->where('action', 'like', $filters->module . '.%');
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate($filters->perPage)
            ->withQueryString()
            ->through(function (AuditLog $log) {
                $modelLabel = $log->model_type ? class_basename($log->model_type) : null;

                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                    'user' => $log->user ? $log->user->only(['id', 'name', 'email']) : null,
                    'actor_name' => $log->actor_name,
                    'action' => $log->action,
                    'description' => $log->description,
                    'model_type' => $log->model_type,
                    'model_label' => $modelLabel,
                    'model_id' => $log->model_id,
                    'ip' => data_get($log->metadata, 'ip'),
                ];
            });

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $u->only(['id', 'name', 'email']))
            ->all();

        $modules = [
            ['value' => '', 'label' => 'All'],
            ['value' => 'employee', 'label' => 'Employees'],
            ['value' => 'employee_document', 'label' => 'Employee Documents'],
            ['value' => 'applicant', 'label' => 'Applicants'],
            ['value' => 'applicant_document', 'label' => 'Applicant Documents'],
            ['value' => 'leave', 'label' => 'Leaves'],
            ['value' => 'attendance', 'label' => 'Attendance'],
            ['value' => 'payroll', 'label' => 'Payroll'],
            ['value' => 'relations', 'label' => 'Employee Relations'],
            ['value' => 'document', 'label' => 'Downloads'],
            ['value' => 'auth', 'label' => 'Authentication'],
        ];

        return [
            'filters' => $filters->toArray(),
            'logs' => $logs,
            'users' => $users,
            'modules' => $modules,
        ];
    }
}
