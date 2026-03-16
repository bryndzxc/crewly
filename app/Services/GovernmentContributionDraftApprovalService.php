<?php

namespace App\Services;

use App\Models\GovernmentSourceMonitor;
use App\Models\GovernmentUpdateDraft;
use App\Models\PagibigContributionSetting;
use App\Models\PhilhealthContributionSetting;
use App\Models\SssContributionTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GovernmentContributionDraftApprovalService
{
    public function approve(GovernmentUpdateDraft $draft, int $reviewerUserId, ?string $notes = null): GovernmentUpdateDraft
    {
        if ($draft->status !== GovernmentUpdateDraft::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only drafts in DRAFT status can be approved.');
        }

        if (!empty($draft->parse_error)) {
            throw new InvalidArgumentException('This draft cannot be approved because parsing failed.');
        }

        $payload = $draft->parsed_payload;
        if (!is_array($payload) || count($payload) === 0) {
            throw new InvalidArgumentException('This draft cannot be approved because the payload is empty.');
        }

        return DB::transaction(function () use ($draft, $reviewerUserId, $notes) {
            $sourceType = (string) $draft->source_type;

            if ($sourceType === GovernmentSourceMonitor::TYPE_SSS) {
                $this->applySss($draft);
            } elseif ($sourceType === GovernmentSourceMonitor::TYPE_PHILHEALTH) {
                $this->applyPhilhealth($draft);
            } elseif ($sourceType === GovernmentSourceMonitor::TYPE_PAGIBIG) {
                $this->applyPagibig($draft);
            } else {
                throw new InvalidArgumentException('Unknown source_type.');
            }

            $draft->forceFill([
                'status' => GovernmentUpdateDraft::STATUS_APPROVED,
                'reviewed_by' => $reviewerUserId,
                'reviewed_at' => now(),
                'notes' => $notes,
            ])->save();

            GovernmentSourceMonitor::query()
                ->where('source_type', $sourceType)
                ->update(['last_status' => GovernmentSourceMonitor::STATUS_OK]);

            return $draft->refresh();
        });
    }

    public function reject(GovernmentUpdateDraft $draft, int $reviewerUserId, ?string $notes = null): GovernmentUpdateDraft
    {
        if ($draft->status !== GovernmentUpdateDraft::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only drafts in DRAFT status can be rejected.');
        }

        $draft->forceFill([
            'status' => GovernmentUpdateDraft::STATUS_REJECTED,
            'reviewed_by' => $reviewerUserId,
            'reviewed_at' => now(),
            'notes' => $notes,
        ])->save();

        return $draft->refresh();
    }

    private function applyPhilhealth(GovernmentUpdateDraft $draft): void
    {
        $payload = $draft->parsed_payload;
        if (is_array($payload) && array_is_list($payload)) {
            $payload = $payload[0] ?? null;
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Invalid PhilHealth payload.');
        }

        $effectiveFrom = $this->requireDate((string) ($payload['effective_from'] ?? ''));

        // Close currently active rows that would overlap.
        PhilhealthContributionSetting::query()
            ->whereNull('effective_to')
            ->whereDate('effective_from', '<', $effectiveFrom->toDateString())
            ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

        PhilhealthContributionSetting::query()->create([
            'effective_from' => $effectiveFrom->toDateString(),
            'effective_to' => $payload['effective_to'] ?? null,
            'premium_rate' => $payload['premium_rate'] ?? null,
            'salary_floor' => $payload['salary_floor'] ?? null,
            'salary_ceiling' => $payload['salary_ceiling'] ?? null,
            'employee_share_percent' => $payload['employee_share_percent'] ?? null,
            'employer_share_percent' => $payload['employer_share_percent'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    private function applyPagibig(GovernmentUpdateDraft $draft): void
    {
        $payload = $draft->parsed_payload;
        if (is_array($payload) && array_is_list($payload)) {
            $payload = $payload[0] ?? null;
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Invalid Pag-IBIG payload.');
        }

        $effectiveFrom = $this->requireDate((string) ($payload['effective_from'] ?? ''));

        PagibigContributionSetting::query()
            ->whereNull('effective_to')
            ->whereDate('effective_from', '<', $effectiveFrom->toDateString())
            ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

        PagibigContributionSetting::query()->create([
            'effective_from' => $effectiveFrom->toDateString(),
            'effective_to' => $payload['effective_to'] ?? null,
            'employee_rate_below_threshold' => $payload['employee_rate_below_threshold'] ?? null,
            'employee_rate_above_threshold' => $payload['employee_rate_above_threshold'] ?? null,
            'employer_rate' => $payload['employer_rate'] ?? null,
            'salary_threshold' => $payload['salary_threshold'] ?? null,
            'monthly_cap' => $payload['monthly_cap'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    private function applySss(GovernmentUpdateDraft $draft): void
    {
        $payload = $draft->parsed_payload;
        if (!is_array($payload) || !array_is_list($payload)) {
            throw new InvalidArgumentException('Invalid SSS payload.');
        }

        $first = $payload[0] ?? null;
        if (!is_array($first)) {
            throw new InvalidArgumentException('Invalid SSS payload row.');
        }

        $effectiveFrom = $this->requireDate((string) ($first['effective_from'] ?? ''));

        // Close currently active SSS rows.
        SssContributionTable::query()
            ->whereNull('effective_to')
            ->whereDate('effective_from', '<', $effectiveFrom->toDateString())
            ->update(['effective_to' => $effectiveFrom->copy()->subDay()->toDateString()]);

        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            SssContributionTable::query()->create([
                'effective_from' => $this->requireDate((string) ($row['effective_from'] ?? ''))->toDateString(),
                'effective_to' => $row['effective_to'] ?? null,
                'range_from' => $row['range_from'] ?? null,
                'range_to' => $row['range_to'] ?? null,
                'monthly_salary_credit' => $row['monthly_salary_credit'] ?? null,
                'employee_share' => $row['employee_share'] ?? null,
                'employer_share' => $row['employer_share'] ?? null,
                'ec_share' => $row['ec_share'] ?? null,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    private function requireDate(string $value): Carbon
    {
        $t = trim($value);
        if ($t === '') {
            throw new InvalidArgumentException('Missing effective_from date.');
        }

        return Carbon::parse($t)->startOfDay();
    }
}
