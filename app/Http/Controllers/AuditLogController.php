<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'action' => $request->string('action')->toString(),
            'module' => $request->string('module')->toString(),
            'user_id' => $request->integer('user_id') ?: null,
            'per_page' => min(max((int) $request->input('per_page', 15), 5), 100),
        ];

        $query = AuditLog::query()->with('user:id,name,email');

        if ($filters['from']) {
            $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
        }
        if ($filters['to']) {
            $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
        }
        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }
        if ($filters['action']) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }
        if ($filters['module']) {
            $query->where('action', 'like', $filters['module'] . '.%');
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(function (AuditLog $log) {
                $modelLabel = null;
                if ($log->model_type) {
                    $modelLabel = class_basename($log->model_type);
                }

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
            ->map(fn (User $u) => $u->only(['id', 'name', 'email']));

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

        return Inertia::render('AuditLogs/Index', [
            'filters' => $filters,
            'logs' => $logs,
            'users' => $users,
            'modules' => $modules,
        ]);
    }

    public function show(Request $request, AuditLog $auditLog): Response
    {
        $auditLog->load('user:id,name,email');

        return Inertia::render('AuditLogs/Show', [
            'log' => [
                'id' => $auditLog->id,
                'created_at' => $auditLog->created_at?->format('Y-m-d H:i:s'),
                'user' => $auditLog->user ? $auditLog->user->only(['id', 'name', 'email']) : null,
                'actor_name' => $auditLog->actor_name,
                'action' => $auditLog->action,
                'description' => $auditLog->description,
                'model_type' => $auditLog->model_type,
                'model_id' => $auditLog->model_id,
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'metadata' => $auditLog->metadata,
            ],
        ]);
    }
}
