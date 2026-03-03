<?php

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMyCashAdvanceRequest;
use App\Services\CashAdvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyCashAdvanceController extends Controller
{
    public function __construct(private readonly CashAdvanceService $cashAdvanceService) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-my-portal');

        return Inertia::render('My/CashAdvances/Index', $this->cashAdvanceService->myIndex($request));
    }

    public function create(Request $request): Response
    {
        $this->authorize('access-my-portal');

        return Inertia::render('My/CashAdvances/Create');
    }

    public function store(StoreMyCashAdvanceRequest $request): RedirectResponse
    {
        $this->authorize('access-my-portal');

        $cashAdvance = $this->cashAdvanceService->createMy($request, $request->validated());

        return to_route('my.cash_advances.index')
            ->with('success', 'Cash advance request submitted.')
            ->setStatusCode(303);
    }
}
