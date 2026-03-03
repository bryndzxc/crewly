<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveCashAdvanceRequest;
use App\Http\Requests\RejectCashAdvanceRequest;
use App\Models\CashAdvance;
use App\Services\CashAdvanceService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CashAdvanceDecisionController extends Controller
{
    public function __construct(private readonly CashAdvanceService $cashAdvanceService) {}

    public function approve(ApproveCashAdvanceRequest $request, CashAdvance $cashAdvance): RedirectResponse
    {
        $this->authorize('approve', $cashAdvance);

        try {
            $this->cashAdvanceService->approve($cashAdvance, $request->validated(), $request->user()?->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return to_route('cash_advances.show', $cashAdvance)
            ->with('success', 'Cash advance approved.')
            ->setStatusCode(303);
    }

    public function reject(RejectCashAdvanceRequest $request, CashAdvance $cashAdvance): RedirectResponse
    {
        $this->authorize('reject', $cashAdvance);

        try {
            $this->cashAdvanceService->reject($cashAdvance, $request->validated(), $request->user()?->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return to_route('cash_advances.show', $cashAdvance)
            ->with('success', 'Cash advance rejected.')
            ->setStatusCode(303);
    }
}
