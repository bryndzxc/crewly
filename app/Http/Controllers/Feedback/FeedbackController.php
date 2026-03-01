<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Feedback/Create', [
            'pageUrl' => $request->fullUrl(),
        ]);
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validated();

        Feedback::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'message' => (string) $validated['message'],
            'page_url' => $validated['page_url'] ?? null,
        ]);

        return back(303)->with('success', 'Feedback received — thank you.');
    }
}
