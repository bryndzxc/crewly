<?php

namespace App\Services;

use App\Repositories\DashboardRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class DashboardService extends Service
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {}

    public function data(Request $request): array
    {
        $today = Carbon::today();
        $windowDays = (int) config('crewly.documents.expiring_soon_days', 30);
        $windowEnd = $today->copy()->addDays($windowDays);

        $probationDays = 30;
        $probationEnd = $today->copy()->addDays($probationDays);

        $allowedStatuses = (array) config('crewly.employees.probation_statuses', ['Active']);
        $allowedStatuses = array_values(array_filter(array_map('strval', $allowedStatuses)));

        $employeesCount = $this->dashboardRepository->employeesCount();
        $expiring30Count = $this->dashboardRepository->expiringDocumentsCount($today, $windowEnd);
        $expiredCount = $this->dashboardRepository->expiredDocumentsCount($today);
        $expiringSoonPayload = $this->dashboardRepository->expiringSoonDocumentsPayload($today, $windowEnd, 5);

        $probationEndingCount = $this->dashboardRepository->probationEndingCount($today, $probationEnd, $allowedStatuses);
        $probationEndingSoon = $this->dashboardRepository->probationEndingSoonPayload($today, $probationEnd, $allowedStatuses, 5);

        $user = $request->user();
        $canSeeLeaves = $user ? Gate::forUser($user)->check('access-leaves') : false;
        $canApproveLeaves = $user ? Gate::forUser($user)->check('approve-leave-requests') : false;

        $pendingApprovalsCount = 0;
        $pendingApprovalsTop5 = [];
        $upcomingApprovedLeavesTop5 = [];

        if ($canSeeLeaves) {
            if ($canApproveLeaves) {
                $pendingApprovalsCount = $this->dashboardRepository->pendingLeaveApprovalsCount();
                $pendingApprovalsTop5 = $this->dashboardRepository->pendingLeaveApprovalsTopPayload(5);
            }

            $leaveWindowEnd = $today->copy()->addDays(30);
            $upcomingApprovedLeavesTop5 = $this->dashboardRepository->upcomingApprovedLeavesTopPayload($today, $leaveWindowEnd, 5);
        }

        return [
            'employees_count' => $employeesCount,
            'expiring_30_count' => $expiring30Count,
            'expired_count' => $expiredCount,
            'expiring_days' => $windowDays,
            'expiring_soon' => $expiringSoonPayload,
            'probation_ending_30_count' => $probationEndingCount,
            'probation_ending_soon' => $probationEndingSoon,
            'can_approve_leaves' => $canApproveLeaves,
            'pending_leave_approvals_count' => $pendingApprovalsCount,
            'pending_leave_approvals_top5' => $pendingApprovalsTop5,
            'upcoming_approved_leaves_top5' => $upcomingApprovedLeavesTop5,
        ];
    }
}
