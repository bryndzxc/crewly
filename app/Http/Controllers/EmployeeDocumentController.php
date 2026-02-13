<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\DocumentCryptoService;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        private readonly DocumentCryptoService $crypto,
        private readonly EmployeeDocumentService $employeeDocumentService
    )
    {
    }

    public function store(StoreEmployeeDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        $files = $request->file('files', []);
        $this->employeeDocumentService->uploadMany($employee, $validated, $files, $request->user()?->id);

        $filesArr = is_array($files) ? $files : [];
        app(\App\Services\AuditLogger::class)->log(
            'employee_document.uploaded',
            $employee,
            [],
            [
                'employee_id' => (int) $employee->employee_id,
                'type' => (string) ($validated['type'] ?? ''),
                'count' => count($filesArr),
                'filenames' => array_values(array_filter(array_map(fn ($f) => $f instanceof \Illuminate\Http\UploadedFile ? $f->getClientOriginalName() : null, $filesArr))),
            ],
            [],
            'Employee documents uploaded.'
        );

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Document(s) uploaded successfully.');
    }

    public function download(Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        if ((int) $document->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        if (!$document->is_encrypted) {
            abort(500, 'Document is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream($document->file_path, $document->encryption_iv, $document->encryption_tag);
        $fileName = $document->original_name ?: 'document';
        $mime = $document->mime_type ?: 'application/octet-stream';

        app(\App\Services\AuditLogger::class)->log(
            'document.downloaded',
            $document,
            [],
            [],
            [
                'module' => 'employees',
                'employee_id' => (int) $employee->employee_id,
                'document_id' => (int) $document->id,
                'type' => (string) $document->type,
                'filename' => (string) ($document->original_name ?? ''),
                'file_size' => (int) ($document->file_size ?? 0),
                'mime_type' => (string) ($document->mime_type ?? ''),
            ],
            'Employee document downloaded.'
        );

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $fileName, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        if ((int) $document->employee_id !== (int) $employee->employee_id) {
            abort(404);
        }

        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        Storage::disk($disk)->delete($document->file_path);

        app(\App\Services\AuditLogger::class)->log(
            'employee_document.deleted',
            $document,
            [
                'employee_id' => (int) $employee->employee_id,
                'document_id' => (int) $document->id,
                'type' => (string) $document->type,
                'filename' => (string) ($document->original_name ?? ''),
                'file_size' => (int) ($document->file_size ?? 0),
            ],
            [],
            ['module' => 'employees'],
            'Employee document deleted.'
        );

        $document->delete();

        return redirect()->route('employees.show', $employee->employee_id)
            ->with('success', 'Document deleted successfully.');
    }
}
