<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crewly_notifications') || !Schema::hasTable('companies')) {
            return;
        }

        if (!Schema::hasColumn('crewly_notifications', 'company_id')) {
            Schema::table('crewly_notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            });
        }

        // Replace the old de-dupe unique index (user_id, dedupe_key) with a tenant-aware one.
        // This avoids cross-company collisions when a user changes companies.
        try {
            Schema::table('crewly_notifications', function (Blueprint $table) {
                $table->dropUnique('crewly_notifications_user_dedupe_unique');
            });
        } catch (Throwable $e) {
            // Best-effort: index may not exist in some environments.
        }

        // Backfill company_id based on referenced entity (best-effort), falling back to the user's company_id.
        DB::table('crewly_notifications')
            ->whereNull('company_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                $leaveRequestIds = [];
                $employeeDocumentIds = [];
                $employeeIds = [];
                $incidentIds = [];
                $userIds = [];

                $parsed = [];

                foreach ($rows as $row) {
                    $userIds[] = (int) ($row->user_id ?? 0);

                    $data = null;
                    if (is_string($row->data ?? null) && trim((string) $row->data) !== '') {
                        $decoded = json_decode((string) $row->data, true);
                        $data = is_array($decoded) ? $decoded : null;
                    } elseif (is_array($row->data ?? null)) {
                        $data = $row->data;
                    }

                    $type = (string) ($row->type ?? '');
                    $ref = null;

                    if ($type === 'LEAVE_PENDING') {
                        $ref = isset($data['leave_request_id']) ? (int) $data['leave_request_id'] : null;
                        if ($ref) {
                            $leaveRequestIds[] = $ref;
                        }
                    } elseif ($type === 'DOC_EXPIRING') {
                        $ref = isset($data['employee_document_id']) ? (int) $data['employee_document_id'] : null;
                        if ($ref) {
                            $employeeDocumentIds[] = $ref;
                        }
                    } elseif ($type === 'PROBATION_ENDING') {
                        $ref = isset($data['employee_id']) ? (int) $data['employee_id'] : null;
                        if ($ref) {
                            $employeeIds[] = $ref;
                        }
                    } elseif ($type === 'INCIDENT_FOLLOWUP') {
                        $ref = isset($data['incident_id']) ? (int) $data['incident_id'] : null;
                        if ($ref) {
                            $incidentIds[] = $ref;
                        }
                    }

                    $parsed[(int) $row->id] = [
                        'type' => $type,
                        'ref' => $ref,
                        'user_id' => (int) ($row->user_id ?? 0),
                    ];
                }

                $leaveCompany = [];
                if (count($leaveRequestIds) > 0 && Schema::hasTable('leave_requests')) {
                    $leaveCompany = DB::table('leave_requests')
                        ->whereIn('id', array_values(array_unique($leaveRequestIds)))
                        ->pluck('company_id', 'id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }

                $docCompany = [];
                if (count($employeeDocumentIds) > 0 && Schema::hasTable('employee_documents')) {
                    $docCompany = DB::table('employee_documents')
                        ->whereIn('id', array_values(array_unique($employeeDocumentIds)))
                        ->pluck('company_id', 'id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }

                $employeeCompany = [];
                if (count($employeeIds) > 0 && Schema::hasTable('employees')) {
                    $employeeCompany = DB::table('employees')
                        ->whereIn('employee_id', array_values(array_unique($employeeIds)))
                        ->pluck('company_id', 'employee_id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }

                $incidentCompany = [];
                if (count($incidentIds) > 0 && Schema::hasTable('employee_incidents')) {
                    $incidentCompany = DB::table('employee_incidents')
                        ->whereIn('id', array_values(array_unique($incidentIds)))
                        ->pluck('company_id', 'id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }

                $userCompany = [];
                $uniqueUserIds = array_values(array_unique(array_filter($userIds, fn ($v) => $v > 0)));
                if (count($uniqueUserIds) > 0 && Schema::hasTable('users')) {
                    $userCompany = DB::table('users')
                        ->whereIn('id', $uniqueUserIds)
                        ->pluck('company_id', 'id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                }

                foreach ($parsed as $notificationId => $meta) {
                    $type = (string) ($meta['type'] ?? '');
                    $ref = (int) ($meta['ref'] ?? 0);
                    $uid = (int) ($meta['user_id'] ?? 0);

                    $companyId = 0;

                    if ($type === 'LEAVE_PENDING' && $ref > 0) {
                        $companyId = (int) ($leaveCompany[$ref] ?? 0);
                    } elseif ($type === 'DOC_EXPIRING' && $ref > 0) {
                        $companyId = (int) ($docCompany[$ref] ?? 0);
                    } elseif ($type === 'PROBATION_ENDING' && $ref > 0) {
                        $companyId = (int) ($employeeCompany[$ref] ?? 0);
                    } elseif ($type === 'INCIDENT_FOLLOWUP' && $ref > 0) {
                        $companyId = (int) ($incidentCompany[$ref] ?? 0);
                    }

                    if ($companyId < 1) {
                        $companyId = (int) ($userCompany[$uid] ?? 0);
                    }

                    if ($companyId < 1) {
                        // Safety fallback: default to the first company.
                        $companyId = (int) (DB::table('companies')->orderBy('id')->value('id') ?? 0);
                    }

                    DB::table('crewly_notifications')
                        ->where('id', $notificationId)
                        ->update([
                            'company_id' => $companyId,
                            'updated_at' => now(),
                        ]);
                }
            });

        // Enforce NOT NULL (DBAL-free).
        DB::statement('ALTER TABLE `crewly_notifications` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('crewly_notifications', function (Blueprint $table) {
            $table->index('company_id', 'crewly_notifications_company_id_idx');
            $table->unique(['user_id', 'company_id', 'dedupe_key'], 'crewly_notifications_user_company_dedupe_unique');
            $table->foreign('company_id', 'crewly_notifications_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crewly_notifications') || !Schema::hasColumn('crewly_notifications', 'company_id')) {
            return;
        }

        Schema::table('crewly_notifications', function (Blueprint $table) {
            $table->dropForeign('crewly_notifications_company_id_fk');
            $table->dropIndex('crewly_notifications_company_id_idx');
            $table->dropUnique('crewly_notifications_user_company_dedupe_unique');
            $table->dropColumn('company_id');
        });

        // Restore the previous unique index for rollbacks.
        try {
            Schema::table('crewly_notifications', function (Blueprint $table) {
                $table->unique(['user_id', 'dedupe_key'], 'crewly_notifications_user_dedupe_unique');
            });
        } catch (Throwable $e) {
            // Best-effort.
        }
    }
};
