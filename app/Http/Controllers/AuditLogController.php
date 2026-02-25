<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('AuditLogs/Index', $this->auditLogService->index($request));
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
