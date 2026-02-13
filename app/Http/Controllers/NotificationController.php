<?php

namespace App\Http\Controllers;

use App\Models\CrewlyNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $filters = [
            'status' => $request->string('status')->toString(), // unread|all
            'type' => $request->string('type')->toString(),
            'per_page' => min(max((int) $request->input('per_page', 15), 5), 100),
        ];

        $items = $this->notifications->paginateFor($user, $filters);

        $types = [
            ['value' => '', 'label' => 'All'],
            ['value' => 'DOC_EXPIRING', 'label' => 'Document Expiry'],
            ['value' => 'PROBATION_ENDING', 'label' => 'Probation Ending'],
            ['value' => 'INCIDENT_FOLLOWUP', 'label' => 'Incident Follow-up'],
            ['value' => 'LEAVE_PENDING', 'label' => 'Leave Requests'],
        ];

        return Inertia::render('Notifications/Index', [
            'filters' => $filters,
            'notifications' => $items,
            'types' => $types,
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json([
            'unread_count' => $this->notifications->unreadCountFor($user),
            'latest' => $this->notifications->latestFor($user, 5),
        ]);
    }

    public function markRead(Request $request, CrewlyNotification $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->notifications->markReadForUser($notification, $user);

        return back()->with('success', 'Notification marked as read.')->setStatusCode(303);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $count = $this->notifications->markAllReadForUser($user);

        return back()->with('success', $count > 0 ? 'All notifications marked as read.' : 'No unread notifications.')->setStatusCode(303);
    }
}
