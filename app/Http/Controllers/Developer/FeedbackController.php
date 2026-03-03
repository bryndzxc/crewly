<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRelationAttachment;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Feedback::query()
            ->with([
                'company:id,name',
                'user:id,name,email,company_id',
            ])
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $feedbackIds = collect($items->items())->pluck('id')->filter()->values()->all();

        $attachmentsByFeedbackId = [];
        if (count($feedbackIds) > 0) {
            $attachmentsByFeedbackId = EmployeeRelationAttachment::withoutCompanyScope()
                ->where('attachable_type', Feedback::class)
                ->whereIn('attachable_id', $feedbackIds)
                ->orderBy('id')
                ->get(['id', 'attachable_id', 'original_name', 'mime_type', 'file_size'])
                ->groupBy('attachable_id')
                ->all();
        }

        return Inertia::render('Developer/Feedback/Index', [
            'feedback' => $items->through(function (Feedback $f) use ($attachmentsByFeedbackId) {
                $attachments = $attachmentsByFeedbackId[$f->id] ?? collect();

                return [
                    'id' => (int) $f->id,
                    'message' => (string) ($f->message ?? ''),
                    'page_url' => (string) ($f->page_url ?? ''),
                    'created_at' => $f->created_at?->toISOString(),
                    'company' => $f->company ? ['id' => (int) $f->company->id, 'name' => (string) $f->company->name] : null,
                    'user' => $f->user ? ['id' => (int) $f->user->id, 'name' => (string) $f->user->name, 'email' => (string) $f->user->email] : null,
                    'attachments' => $attachments
                        ->map(fn (EmployeeRelationAttachment $a) => [
                            'id' => (int) $a->id,
                            'original_name' => (string) ($a->original_name ?? 'attachment'),
                            'mime_type' => (string) ($a->mime_type ?? ''),
                            'file_size' => (int) ($a->file_size ?? 0),
                        ])
                        ->values()
                        ->all(),
                ];
            }),
        ]);
    }
}
