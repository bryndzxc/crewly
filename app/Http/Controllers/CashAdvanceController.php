<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Services\CashAdvanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashAdvanceController extends Controller
{
    public function __construct(private readonly CashAdvanceService $cashAdvanceService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CashAdvance::class);

        return Inertia::render('CashAdvances/Index', $this->cashAdvanceService->index($request));
    }

    public function show(Request $request, CashAdvance $cashAdvance): Response
    {
        $this->authorize('view', $cashAdvance);

        return Inertia::render('CashAdvances/Show', $this->cashAdvanceService->show($request, $cashAdvance));
    }
}
