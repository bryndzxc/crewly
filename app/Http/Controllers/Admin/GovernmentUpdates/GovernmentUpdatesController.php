<?php

namespace App\Http\Controllers\Admin\GovernmentUpdates;

use App\Http\Controllers\Controller;
use App\Models\GovernmentSourceMonitor;
use App\Models\GovernmentUpdateDraft;
use App\Models\PagibigContributionSetting;
use App\Models\PhilhealthContributionSetting;
use App\Models\SssContributionTable;
use App\Services\GovernmentContributionDraftApprovalService;
use App\Services\GovernmentContributionMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class GovernmentUpdatesController extends Controller
{
    public function index(Request $request): Response
    {
        $sourceType = trim((string) $request->query('source_type', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 5), 100);

        foreach ([GovernmentSourceMonitor::TYPE_SSS, GovernmentSourceMonitor::TYPE_PHILHEALTH, GovernmentSourceMonitor::TYPE_PAGIBIG] as $type) {
            $url = trim((string) config("government_monitor.sources.{$type}.url", ''));

            GovernmentSourceMonitor::query()->firstOrCreate(
                ['source_type' => $type],
                ['source_url' => $url]
            );
        }

        $monitors = GovernmentSourceMonitor::query()
            ->with(['latestDraft'])
            ->orderByRaw("case source_type when 'sss' then 0 when 'philhealth' then 1 when 'pagibig' then 2 else 9 end")
            ->get();

        $draftQuery = GovernmentUpdateDraft::query()->orderByDesc('detected_at');
        $draftQuery->where('status', GovernmentUpdateDraft::STATUS_DRAFT);

        if ($sourceType !== '') {
            $draftQuery->where('source_type', $sourceType);
        }

        $drafts = $draftQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (GovernmentUpdateDraft $d) => [
                'id' => (int) $d->id,
                'source_type' => (string) $d->source_type,
                'detected_at' => $d->detected_at?->format('Y-m-d H:i:s'),
                'source_url' => (string) $d->source_url,
                'content_hash' => (string) $d->content_hash,
                'status' => (string) $d->status,
            ]);

        return Inertia::render('Admin/GovernmentUpdates/Index', [
            'filters' => [
                'source_type' => $sourceType,
                'per_page' => $perPage,
            ],
            'monitors' => $monitors->map(fn (GovernmentSourceMonitor $m) => [
                'id' => (int) $m->id,
                'source_type' => (string) $m->source_type,
                'source_url' => (string) $m->source_url,
                'last_checked_at' => $m->last_checked_at?->format('Y-m-d H:i:s'),
                'last_status' => (string) ($m->last_status ?? ''),
                'last_error' => (string) ($m->last_error ?? ''),
                'latest_draft' => $m->latestDraft ? [
                    'id' => (int) $m->latestDraft->id,
                    'status' => (string) $m->latestDraft->status,
                    'detected_at' => $m->latestDraft->detected_at?->format('Y-m-d H:i:s'),
                ] : null,
            ])->values()->all(),
            'drafts' => $drafts,
            'source_types' => [GovernmentSourceMonitor::TYPE_SSS, GovernmentSourceMonitor::TYPE_PHILHEALTH, GovernmentSourceMonitor::TYPE_PAGIBIG],
        ]);
    }

    public function checkAll(GovernmentContributionMonitorService $service): RedirectResponse
    {
        $service->checkAll();

        return redirect()->back()->with('success', 'Government sources checked.')->setStatusCode(303);
    }

    public function checkOne(string $sourceType, GovernmentContributionMonitorService $service): RedirectResponse
    {
        if ($sourceType === GovernmentSourceMonitor::TYPE_SSS) {
            $service->checkSSS();
        } elseif ($sourceType === GovernmentSourceMonitor::TYPE_PHILHEALTH) {
            $service->checkPhilHealth();
        } elseif ($sourceType === GovernmentSourceMonitor::TYPE_PAGIBIG) {
            $service->checkPagibig();
        }

        return redirect()->back()->with('success', strtoupper($sourceType).' source checked.')->setStatusCode(303);
    }

    public function showDraft(Request $request, GovernmentUpdateDraft $draft): Response
    {
        $active = $this->getActiveSnapshotForDraft($draft);

        return Inertia::render('Admin/GovernmentUpdates/Show', [
            'draft' => [
                'id' => (int) $draft->id,
                'source_type' => (string) $draft->source_type,
                'detected_at' => $draft->detected_at?->format('Y-m-d H:i:s'),
                'source_url' => (string) $draft->source_url,
                'content_hash' => (string) $draft->content_hash,
                'status' => (string) $draft->status,
                'parsed_payload' => $draft->parsed_payload,
                'parse_error' => (string) ($draft->parse_error ?? ''),
                'reviewed_by' => $draft->reviewed_by,
                'reviewed_at' => $draft->reviewed_at?->format('Y-m-d H:i:s'),
                'notes' => (string) ($draft->notes ?? ''),
            ],
            'active' => $active,
        ]);
    }

    public function approveDraft(Request $request, GovernmentUpdateDraft $draft, GovernmentContributionDraftApprovalService $approval): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $approval->approve($draft, (int) $request->user()->id, $validated['notes'] ?? null);

        return redirect()->route('admin.government_updates.drafts.show', $draft->id)
            ->with('success', 'Draft approved and applied.')
            ->setStatusCode(303);
    }

    public function rejectDraft(Request $request, GovernmentUpdateDraft $draft, GovernmentContributionDraftApprovalService $approval): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $approval->reject($draft, (int) $request->user()->id, $validated['notes'] ?? null);

        return redirect()->route('admin.government_updates.index')
            ->with('success', 'Draft rejected.')
            ->setStatusCode(303);
    }

    private function getActiveSnapshotForDraft(GovernmentUpdateDraft $draft): array
    {
        $payload = $draft->parsed_payload;
        $sourceType = (string) $draft->source_type;

        $effectiveFrom = null;

        if ($sourceType === GovernmentSourceMonitor::TYPE_SSS) {
            if (is_array($payload) && array_is_list($payload) && isset($payload[0]['effective_from'])) {
                $effectiveFrom = (string) $payload[0]['effective_from'];
            }
        } else {
            if (is_array($payload) && isset($payload['effective_from'])) {
                $effectiveFrom = (string) $payload['effective_from'];
            }
            if (is_array($payload) && array_is_list($payload) && isset($payload[0]['effective_from'])) {
                $effectiveFrom = (string) $payload[0]['effective_from'];
            }
        }

        $date = $effectiveFrom ? Carbon::parse($effectiveFrom) : now();

        if ($sourceType === GovernmentSourceMonitor::TYPE_PHILHEALTH) {
            $active = PhilhealthContributionSetting::query()->activeOn($date)->orderByDesc('effective_from')->first();
            return [
                'type' => 'philhealth',
                'effective_on' => $date->toDateString(),
                'row' => $active ? $active->only([
                    'effective_from',
                    'effective_to',
                    'premium_rate',
                    'salary_floor',
                    'salary_ceiling',
                    'employee_share_percent',
                    'employer_share_percent',
                    'notes',
                ]) : null,
            ];
        }

        if ($sourceType === GovernmentSourceMonitor::TYPE_PAGIBIG) {
            $active = PagibigContributionSetting::query()->activeOn($date)->orderByDesc('effective_from')->first();
            return [
                'type' => 'pagibig',
                'effective_on' => $date->toDateString(),
                'row' => $active ? $active->only([
                    'effective_from',
                    'effective_to',
                    'employee_rate_below_threshold',
                    'employee_rate_above_threshold',
                    'employer_rate',
                    'salary_threshold',
                    'monthly_cap',
                    'notes',
                ]) : null,
            ];
        }

        // SSS
        $activeRows = SssContributionTable::query()
            ->activeOn($date)
            ->orderBy('range_from')
            ->limit(500)
            ->get([
                'effective_from',
                'effective_to',
                'range_from',
                'range_to',
                'monthly_salary_credit',
                'employee_share',
                'employer_share',
                'ec_share',
                'notes',
            ]);

        return [
            'type' => 'sss',
            'effective_on' => $date->toDateString(),
            'count' => $activeRows->count(),
            'rows' => $activeRows,
        ];
    }
}
