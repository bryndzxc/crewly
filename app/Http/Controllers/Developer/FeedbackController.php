<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
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

        return Inertia::render('Developer/Feedback/Index', [
            'feedback' => $items,
        ]);
    }
}
