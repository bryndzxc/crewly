<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Services\EmployeeRelationAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function __construct(private readonly EmployeeRelationAttachmentService $attachmentService)
    {
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Feedback/Create', [
            'pageUrl' => $request->fullUrl(),
            'prefillMessage' => (string) $request->query('message', ''),
        ]);
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validated();

        $files = $request->file('attachments', []);
        $files = is_array($files) ? $files : [];

        $feedback = Feedback::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'message' => (string) $validated['message'],
            'page_url' => $validated['page_url'] ?? null,
        ]);

        if (count($files) > 0) {
            $this->attachmentService->uploadMany($feedback, $files, 'concern', $user->id);
        }

        return back(303)->with('success', 'Concern received — thank you.');
    }
}
