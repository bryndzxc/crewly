<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        $defaultCompanyId = $this->ensureDefaultCompanyId();

        $this->addCompanyIdToUsers($defaultCompanyId);

        $this->addCompanyIdToEmployees($defaultCompanyId);
        $this->addCompanyIdToEmployeeRelations($defaultCompanyId);
        $this->addCompanyIdToEmployeeDocuments($defaultCompanyId);
        $this->addCompanyIdToAttendanceRecords($defaultCompanyId);

        $this->addCompanyIdToMemos($defaultCompanyId);
        $this->addCompanyIdToMemoTemplates($defaultCompanyId);

        $this->addCompanyIdToRecruitment($defaultCompanyId);

        // Not explicitly required, but these are core HR tables and should be tenant-owned.
        $this->addCompanyIdToDepartments($defaultCompanyId);
        $this->addCompanyIdToLeaves($defaultCompanyId);
    }

    public function down(): void
    {
        // Best-effort rollback. Keep companies table for safety.
        $this->dropCompanyIdForeignAndColumn('leave_balances', 'leave_balances_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('leave_requests', 'leave_requests_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('leave_types', 'leave_types_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('departments', 'departments_company_id_fk');

        $this->dropCompanyIdForeignAndColumn('applicant_interviews', 'applicant_interviews_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('applicant_documents', 'applicant_documents_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('applicants', 'applicants_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('recruitment_positions', 'recruitment_positions_company_id_fk');

        $this->dropCompanyIdForeignAndColumn('memo_templates', 'memo_templates_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('memos', 'memos_company_id_fk');

        $this->dropCompanyIdForeignAndColumn('attendance_records', 'attendance_records_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('employee_relation_attachments', 'employee_relation_attachments_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('employee_documents', 'employee_documents_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('employee_notes', 'employee_notes_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('employee_incidents', 'employee_incidents_company_id_fk');
        $this->dropCompanyIdForeignAndColumn('employees', 'employees_company_id_fk');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_company_id_fk');
                $table->dropIndex('users_company_id_idx');
                $table->dropColumn('company_id');
            });
        }
    }

    private function ensureDefaultCompanyId(): int
    {
        $existing = (int) (DB::table('companies')->orderBy('id')->value('id') ?? 0);
        if ($existing > 0) {
            return $existing;
        }

        $timezone = (string) config('app.timezone', 'Asia/Manila');

        $id = DB::table('companies')->insertGetId([
            'name' => 'Default Company',
            'slug' => 'default',
            'logo_path' => null,
            'address' => null,
            'timezone' => $timezone,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }

    private function addCompanyIdToUsers(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        DB::table('users')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);

        // Enforce NOT NULL (DBAL-free).
        DB::statement('ALTER TABLE `users` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->index('company_id', 'users_company_id_idx');
            $table->foreign('company_id', 'users_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToEmployees(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }

        if (!Schema::hasColumn('employees', 'company_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('employee_id');
            });
        }

        if (Schema::hasColumn('employees', 'user_id')) {
            DB::statement(
                'UPDATE `employees` e '
                . 'JOIN `users` u ON u.id = e.user_id '
                . 'SET e.company_id = u.company_id '
                . 'WHERE e.company_id IS NULL AND e.user_id IS NOT NULL'
            );
        }

        if (Schema::hasColumn('employees', 'created_by')) {
            DB::statement(
                'UPDATE `employees` e '
                . 'JOIN `users` u ON u.id = e.created_by '
                . 'SET e.company_id = u.company_id '
                . 'WHERE e.company_id IS NULL AND e.created_by IS NOT NULL'
            );
        }

        DB::table('employees')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);

        DB::statement('ALTER TABLE `employees` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('employees', function (Blueprint $table) {
            $table->index('company_id', 'employees_company_id_idx');
            $table->foreign('company_id', 'employees_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToEmployeeRelations(int $defaultCompanyId): void
    {
        // employee_incidents
        if (Schema::hasTable('employee_incidents')) {
            if (!Schema::hasColumn('employee_incidents', 'company_id')) {
                Schema::table('employee_incidents', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `employee_incidents` i '
                . 'JOIN `employees` e ON e.employee_id = i.employee_id '
                . 'SET i.company_id = e.company_id '
                . 'WHERE i.company_id IS NULL'
            );
            DB::table('employee_incidents')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `employee_incidents` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('employee_incidents', function (Blueprint $table) {
                $table->index('company_id', 'employee_incidents_company_id_idx');
                $table->foreign('company_id', 'employee_incidents_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // employee_notes
        if (Schema::hasTable('employee_notes')) {
            if (!Schema::hasColumn('employee_notes', 'company_id')) {
                Schema::table('employee_notes', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `employee_notes` n '
                . 'JOIN `employees` e ON e.employee_id = n.employee_id '
                . 'SET n.company_id = e.company_id '
                . 'WHERE n.company_id IS NULL'
            );
            DB::table('employee_notes')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `employee_notes` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('employee_notes', function (Blueprint $table) {
                $table->index('company_id', 'employee_notes_company_id_idx');
                $table->foreign('company_id', 'employee_notes_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // employee_relation_attachments
        if (Schema::hasTable('employee_relation_attachments')) {
            if (!Schema::hasColumn('employee_relation_attachments', 'company_id')) {
                Schema::table('employee_relation_attachments', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            if (Schema::hasColumn('employee_relation_attachments', 'uploaded_by')) {
                DB::statement(
                    'UPDATE `employee_relation_attachments` a '
                    . 'JOIN `users` u ON u.id = a.uploaded_by '
                    . 'SET a.company_id = u.company_id '
                    . 'WHERE a.company_id IS NULL AND a.uploaded_by IS NOT NULL'
                );
            }
            DB::table('employee_relation_attachments')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `employee_relation_attachments` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('employee_relation_attachments', function (Blueprint $table) {
                $table->index('company_id', 'employee_relation_attachments_company_id_idx');
                $table->foreign('company_id', 'employee_relation_attachments_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }
    }

    private function addCompanyIdToEmployeeDocuments(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('employee_documents')) {
            return;
        }

        if (!Schema::hasColumn('employee_documents', 'company_id')) {
            Schema::table('employee_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        DB::statement(
            'UPDATE `employee_documents` d '
            . 'JOIN `employees` e ON e.employee_id = d.employee_id '
            . 'SET d.company_id = e.company_id '
            . 'WHERE d.company_id IS NULL'
        );

        DB::table('employee_documents')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        DB::statement('ALTER TABLE `employee_documents` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('employee_documents', function (Blueprint $table) {
            $table->index('company_id', 'employee_documents_company_id_idx');
            $table->foreign('company_id', 'employee_documents_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToAttendanceRecords(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        if (!Schema::hasColumn('attendance_records', 'company_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            });
        }

        DB::statement(
            'UPDATE `attendance_records` a '
            . 'JOIN `employees` e ON e.employee_id = a.employee_id '
            . 'SET a.company_id = e.company_id '
            . 'WHERE a.company_id IS NULL'
        );

        DB::table('attendance_records')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        DB::statement('ALTER TABLE `attendance_records` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index('company_id', 'attendance_records_company_id_idx');
            $table->foreign('company_id', 'attendance_records_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToMemos(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('memos') || !Schema::hasColumn('memos', 'company_id')) {
            return;
        }

        DB::statement(
            'UPDATE `memos` m '
            . 'JOIN `employees` e ON e.employee_id = m.employee_id '
            . 'SET m.company_id = e.company_id '
            . 'WHERE m.company_id IS NULL'
        );

        DB::table('memos')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        DB::statement('ALTER TABLE `memos` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('memos', function (Blueprint $table) {
            $table->index('company_id', 'memos_company_id_idx');
            $table->foreign('company_id', 'memos_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToMemoTemplates(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('memo_templates') || !Schema::hasColumn('memo_templates', 'company_id')) {
            return;
        }

        if (Schema::hasColumn('memo_templates', 'created_by_user_id')) {
            DB::statement(
                'UPDATE `memo_templates` t '
                . 'JOIN `users` u ON u.id = t.created_by_user_id '
                . 'SET t.company_id = u.company_id '
                . 'WHERE t.company_id IS NULL AND t.created_by_user_id IS NOT NULL'
            );
        }

        DB::table('memo_templates')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        DB::statement('ALTER TABLE `memo_templates` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('memo_templates', function (Blueprint $table) {
            $table->index('company_id', 'memo_templates_company_id_idx');
            $table->foreign('company_id', 'memo_templates_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToRecruitment(int $defaultCompanyId): void
    {
        // recruitment_positions
        if (Schema::hasTable('recruitment_positions')) {
            if (!Schema::hasColumn('recruitment_positions', 'company_id')) {
                Schema::table('recruitment_positions', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            if (Schema::hasColumn('recruitment_positions', 'created_by')) {
                DB::statement(
                    'UPDATE `recruitment_positions` p '
                    . 'JOIN `users` u ON u.id = p.created_by '
                    . 'SET p.company_id = u.company_id '
                    . 'WHERE p.company_id IS NULL AND p.created_by IS NOT NULL'
                );
            }

            DB::table('recruitment_positions')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `recruitment_positions` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('recruitment_positions', function (Blueprint $table) {
                $table->index('company_id', 'recruitment_positions_company_id_idx');
                $table->foreign('company_id', 'recruitment_positions_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // applicants
        if (Schema::hasTable('applicants')) {
            if (!Schema::hasColumn('applicants', 'company_id')) {
                Schema::table('applicants', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            if (Schema::hasColumn('applicants', 'created_by')) {
                DB::statement(
                    'UPDATE `applicants` a '
                    . 'JOIN `users` u ON u.id = a.created_by '
                    . 'SET a.company_id = u.company_id '
                    . 'WHERE a.company_id IS NULL AND a.created_by IS NOT NULL'
                );
            }

            DB::table('applicants')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `applicants` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('applicants', function (Blueprint $table) {
                $table->index('company_id', 'applicants_company_id_idx');
                $table->foreign('company_id', 'applicants_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // applicant_documents
        if (Schema::hasTable('applicant_documents')) {
            if (!Schema::hasColumn('applicant_documents', 'company_id')) {
                Schema::table('applicant_documents', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `applicant_documents` d '
                . 'JOIN `applicants` a ON a.id = d.applicant_id '
                . 'SET d.company_id = a.company_id '
                . 'WHERE d.company_id IS NULL'
            );

            DB::table('applicant_documents')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `applicant_documents` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('applicant_documents', function (Blueprint $table) {
                $table->index('company_id', 'applicant_documents_company_id_idx');
                $table->foreign('company_id', 'applicant_documents_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // applicant_interviews
        if (Schema::hasTable('applicant_interviews')) {
            if (!Schema::hasColumn('applicant_interviews', 'company_id')) {
                Schema::table('applicant_interviews', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `applicant_interviews` i '
                . 'JOIN `applicants` a ON a.id = i.applicant_id '
                . 'SET i.company_id = a.company_id '
                . 'WHERE i.company_id IS NULL'
            );

            DB::table('applicant_interviews')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `applicant_interviews` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('applicant_interviews', function (Blueprint $table) {
                $table->index('company_id', 'applicant_interviews_company_id_idx');
                $table->foreign('company_id', 'applicant_interviews_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }
    }

    private function addCompanyIdToDepartments(int $defaultCompanyId): void
    {
        if (!Schema::hasTable('departments')) {
            return;
        }

        if (!Schema::hasColumn('departments', 'company_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('department_id');
            });
        }

        DB::table('departments')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        DB::statement('ALTER TABLE `departments` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('departments', function (Blueprint $table) {
            $table->index('company_id', 'departments_company_id_idx');
            $table->foreign('company_id', 'departments_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    private function addCompanyIdToLeaves(int $defaultCompanyId): void
    {
        // leave_types
        if (Schema::hasTable('leave_types')) {
            if (!Schema::hasColumn('leave_types', 'company_id')) {
                Schema::table('leave_types', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::table('leave_types')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `leave_types` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('leave_types', function (Blueprint $table) {
                $table->index('company_id', 'leave_types_company_id_idx');
                $table->foreign('company_id', 'leave_types_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // leave_requests
        if (Schema::hasTable('leave_requests')) {
            if (!Schema::hasColumn('leave_requests', 'company_id')) {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `leave_requests` r '
                . 'JOIN `employees` e ON e.employee_id = r.employee_id '
                . 'SET r.company_id = e.company_id '
                . 'WHERE r.company_id IS NULL'
            );
            DB::table('leave_requests')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `leave_requests` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('leave_requests', function (Blueprint $table) {
                $table->index('company_id', 'leave_requests_company_id_idx');
                $table->foreign('company_id', 'leave_requests_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }

        // leave_balances
        if (Schema::hasTable('leave_balances')) {
            if (!Schema::hasColumn('leave_balances', 'company_id')) {
                Schema::table('leave_balances', function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }

            DB::statement(
                'UPDATE `leave_balances` b '
                . 'JOIN `employees` e ON e.employee_id = b.employee_id '
                . 'SET b.company_id = e.company_id '
                . 'WHERE b.company_id IS NULL'
            );
            DB::table('leave_balances')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            DB::statement('ALTER TABLE `leave_balances` MODIFY `company_id` BIGINT UNSIGNED NOT NULL');

            Schema::table('leave_balances', function (Blueprint $table) {
                $table->index('company_id', 'leave_balances_company_id_idx');
                $table->foreign('company_id', 'leave_balances_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->restrictOnDelete();
            });
        }
    }

    private function dropCompanyIdForeignAndColumn(string $tableName, string $foreignKeyName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'company_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName, $tableName) {
            try {
                $table->dropForeign($foreignKeyName);
            } catch (Throwable $e) {
                // ignore
            }

            $idx = $tableName . '_company_id_idx';
            try {
                $table->dropIndex($idx);
            } catch (Throwable $e) {
                // ignore
            }

            $table->dropColumn('company_id');
        });
    }
};
