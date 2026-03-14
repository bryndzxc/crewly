<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeGovernmentInfoRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;

class EmployeeGovernmentController extends Controller
{
    public function update(UpdateEmployeeGovernmentInfoRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        $employee->fill([
            'sss_number' => $validated['sss_number'] ?? null,
            'philhealth_number' => $validated['philhealth_number'] ?? null,
            'pagibig_number' => $validated['pagibig_number'] ?? null,
            'tin_number' => $validated['tin_number'] ?? null,
        ]);

        $employee->save();

        return back()
            ->with('success', 'Government information updated.')
            ->setStatusCode(303);
    }
}
