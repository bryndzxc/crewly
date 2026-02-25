<?php

namespace App\Http\Controllers;

use App\Services\ExpiringDocumentsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpiringDocumentsController extends Controller
{
    public function __construct(
        private readonly ExpiringDocumentsService $expiringDocumentsService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Documents/ExpiringDocuments/Index', $this->expiringDocumentsService->index($request));
    }
}
