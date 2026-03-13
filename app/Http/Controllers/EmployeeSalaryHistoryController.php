<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeSalaryHistoryController extends Controller
{
    public function index(Request $request, Employee $employee): JsonResponse|RedirectResponse
    {
        $items = EmployeeSalaryHistory::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->with('approvedBy:id,name')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $items->map(fn (EmployeeSalaryHistory $history) => [
                    'id' => $history->id,
                    'employee_id' => $history->employee_id,
                    'previous_salary' => $history->previous_salary,
                    'new_salary' => $history->new_salary,
                    'effective_date' => $history->effective_date?->format('Y-m-d'),
                    'reason' => $history->reason,
                    'approved_by' => $history->approvedBy ? $history->approvedBy->only(['id', 'name']) : null,
                    'created_at' => $history->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $history->updated_at?->format('Y-m-d H:i:s'),
                ])->values()->all(),
            ]);
        }

        return redirect()->route('employees.show', $employee->employee_id);
    }
}