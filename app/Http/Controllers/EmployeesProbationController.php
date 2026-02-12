<?php

namespace App\Http\Controllers;

use App\Services\EmployeesProbationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeesProbationController extends Controller
{
    public function __construct(
        private readonly EmployeesProbationService $employeesProbationService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Employees/Probation', $this->employeesProbationService->index($request));
    }
}
