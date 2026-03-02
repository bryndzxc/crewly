<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DemoCompanyCleanupService extends Service
{
    /**
     * Purge demo/seeded company data.
     *
     * Options:
     * - delete_users (bool): also delete users for this company (default false)
     * - delete_leads (bool): also delete leads for this company (default false)
     * - preserve_leave_types (bool): keep leave types for this company (default false)
     * - preserve_memo_templates (bool): keep memo templates for this company (default false)
     *
     * @param array{delete_users?:bool,delete_leads?:bool,preserve_leave_types?:bool,preserve_memo_templates?:bool} $options
     */
    public function purgeCompanyData(int $companyId, string $companySlug, array $options = []): void
    {
        $deleteUsers = (bool) ($options['delete_users'] ?? false);
        $deleteLeads = (bool) ($options['delete_leads'] ?? false);
        $preserveLeaveTypes = (bool) ($options['preserve_leave_types'] ?? false);
        $preserveMemoTemplates = (bool) ($options['preserve_memo_templates'] ?? false);

        $documentsDisk = (string) config('crewly.documents.disk', config('filesystems.default', 'local'));

        $memoPdfPaths = DB::table('memos')->where('company_id', $companyId)->pluck('pdf_path')->all();
        foreach ($memoPdfPaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
        }

        $employeeDocPaths = DB::table('employee_documents')->where('company_id', $companyId)->pluck('file_path')->all();
        foreach ($employeeDocPaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk($documentsDisk)->delete($path);
            }
        }

        $relationAttachmentPaths = DB::table('employee_relation_attachments')->where('company_id', $companyId)->pluck('file_path')->all();
        foreach ($relationAttachmentPaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk($documentsDisk)->delete($path);
            }
        }

        $employeePhotoPaths = DB::table('employees')
            ->where('company_id', $companyId)
            ->whereNotNull('photo_path')
            ->pluck('photo_path')
            ->all();
        foreach ($employeePhotoPaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk($documentsDisk)->delete($path);
            }
        }

        $applicantDocPaths = DB::table('applicant_documents')->where('company_id', $companyId)->pluck('file_path')->all();
        foreach ($applicantDocPaths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk($documentsDisk)->delete($path);
            }
        }

        if ($deleteUsers) {
            $userProfilePhotos = DB::table('users')
                ->where('company_id', $companyId)
                ->whereNotNull('profile_photo_path')
                ->pluck('profile_photo_path')
                ->all();
            foreach ($userProfilePhotos as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Remove generated memo folders for employees and seed PDFs for the company.
        $employeeIds = DB::table('employees')->where('company_id', $companyId)->pluck('employee_id')->all();
        foreach ($employeeIds as $employeeId) {
            if (!is_numeric($employeeId)) {
                continue;
            }
            Storage::disk('local')->deleteDirectory('private/memos/' . (int) $employeeId);
        }
        Storage::disk('local')->deleteDirectory('memos/seed/' . $companySlug);

        // Delete DB rows (children first).
        DB::table('employee_relation_attachments')->where('company_id', $companyId)->delete();
        DB::table('employee_documents')->where('company_id', $companyId)->delete();
        DB::table('memos')->where('company_id', $companyId)->delete();
        if (!$preserveMemoTemplates) {
            DB::table('memo_templates')->where('company_id', $companyId)->delete();
        }

        DB::table('employee_notes')->where('company_id', $companyId)->delete();
        DB::table('employee_incidents')->where('company_id', $companyId)->delete();
        DB::table('attendance_records')->where('company_id', $companyId)->delete();

        DB::table('leave_balances')->where('company_id', $companyId)->delete();
        DB::table('leave_requests')->where('company_id', $companyId)->delete();
        if (!$preserveLeaveTypes) {
            DB::table('leave_types')->where('company_id', $companyId)->delete();
        }

        DB::table('recruitment_positions')->where('company_id', $companyId)->delete();
        DB::table('applicant_documents')->where('company_id', $companyId)->delete();
        DB::table('applicant_interviews')->where('company_id', $companyId)->delete();
        DB::table('applicants')->where('company_id', $companyId)->delete();

        DB::table('departments')->where('company_id', $companyId)->delete();
        DB::table('employees')->where('company_id', $companyId)->delete();

        if ($deleteUsers) {
            // audit_logs nullOnDelete.
            DB::table('users')->where('company_id', $companyId)->delete();
        }

        if ($deleteLeads) {
            DB::table('leads')->where('company_id', $companyId)->delete();
        }

        Log::warning('Purged demo company data.', [
            'company_id' => $companyId,
            'company_slug' => $companySlug,
            'delete_users' => $deleteUsers,
            'delete_leads' => $deleteLeads,
            'preserve_leave_types' => $preserveLeaveTypes,
            'preserve_memo_templates' => $preserveMemoTemplates,
        ]);
    }
}
