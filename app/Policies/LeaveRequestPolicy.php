<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true);
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER], true);
    }

    public function deny(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return false;
        }

        if ($leaveRequest->requested_by && (int) $leaveRequest->requested_by === (int) $user->id) {
            return true;
        }

        return in_array($user->role(), [User::ROLE_ADMIN, User::ROLE_HR], true);
    }
}
