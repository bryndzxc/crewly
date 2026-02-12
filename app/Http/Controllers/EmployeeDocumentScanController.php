<?php

namespace App\Http\Controllers;

use App\Services\DocumentScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeDocumentScanController extends Controller
{
    public function __invoke(Request $request, DocumentScanService $scanner): JsonResponse
    {
        $maxFiles = (int) config('crewly.scan.max_files', 3);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:' . $maxFiles],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $result = $scanner->scanForEmployeeFields($validated['files']);

        return response()->json($result);
    }
}
