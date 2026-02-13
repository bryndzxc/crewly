<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AuditLogger;
use App\Services\DocumentCryptoService;
use App\Services\EmployeePhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class EmployeePhotoController extends Controller
{
    public function __construct(
        private readonly DocumentCryptoService $crypto,
        private readonly EmployeePhotoService $photos
    )
    {
    }

    public function show(Request $request, Employee $employee)
    {
        $path = (string) ($employee->getAttribute('photo_path') ?? '');
        if ($path === '') {
            abort(404);
        }

        $isDownload = (bool) $request->boolean('download');
        if ($isDownload) {
            app(AuditLogger::class)->log(
                'employee.photo.downloaded',
                $employee,
                [],
                [
                    'employee_id' => (int) $employee->employee_id,
                    'original_name' => (string) ($employee->getAttribute('photo_original_name') ?? ''),
                    'mime_type' => (string) ($employee->getAttribute('photo_mime_type') ?? ''),
                    'size' => (int) ($employee->getAttribute('photo_size') ?? 0),
                ],
                [],
                'Employee photo downloaded.'
            );
        }

        $stream = $this->crypto->decryptToStream(
            $path,
            (string) ($employee->getAttribute('photo_encryption_iv') ?? ''),
            (string) ($employee->getAttribute('photo_encryption_tag') ?? '')
        );

        $mime = (string) ($employee->getAttribute('photo_mime_type') ?? 'application/octet-stream');

        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ];

        if ($isDownload) {
            $rawName = (string) ($employee->getAttribute('photo_original_name') ?? 'photo');
            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $rawName) ?: 'photo';
            $headers['Content-Disposition'] = 'attachment; filename="' . $safeName . '"';
        }

        return response()->stream(function () use ($stream) {
            if (is_resource($stream)) {
                rewind($stream);
                fpassthru($stream);
            }
        }, 200, $headers);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png'],
        ]);

        $before = [
            'has_photo' => (bool) $employee->has_photo,
            'original_name' => (string) ($employee->getAttribute('photo_original_name') ?? ''),
            'mime_type' => (string) ($employee->getAttribute('photo_mime_type') ?? ''),
            'size' => (int) ($employee->getAttribute('photo_size') ?? 0),
        ];

        try {
            $this->photos->setPhoto($employee, $validated['photo']);

            $after = [
                'has_photo' => (bool) $employee->has_photo,
                'original_name' => (string) ($employee->getAttribute('photo_original_name') ?? ''),
                'mime_type' => (string) ($employee->getAttribute('photo_mime_type') ?? ''),
                'size' => (int) ($employee->getAttribute('photo_size') ?? 0),
            ];

            app(AuditLogger::class)->log(
                'employee.photo.updated',
                $employee,
                $before,
                $after,
                [
                    'employee_id' => (int) $employee->employee_id,
                ],
                'Employee photo updated.'
            );

            return to_route('employees.show', $employee->employee_id)
                ->with('success', 'Photo updated successfully.')
                ->setStatusCode(303);
        } catch (\Throwable $e) {
            Log::warning('Employee photo update failed.', [
                'employee_id' => (int) $employee->employee_id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Photo update failed. Please try again.')
                ->setStatusCode(303);
        }
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $before = [
            'has_photo' => (bool) $employee->has_photo,
            'original_name' => (string) ($employee->getAttribute('photo_original_name') ?? ''),
            'mime_type' => (string) ($employee->getAttribute('photo_mime_type') ?? ''),
            'size' => (int) ($employee->getAttribute('photo_size') ?? 0),
        ];

        try {
            $this->photos->deletePhoto($employee);

            app(AuditLogger::class)->log(
                'employee.photo.deleted',
                $employee,
                $before,
                [
                    'has_photo' => false,
                ],
                [
                    'employee_id' => (int) $employee->employee_id,
                ],
                'Employee photo deleted.'
            );

            return to_route('employees.show', $employee->employee_id)
                ->with('success', 'Photo deleted successfully.')
                ->setStatusCode(303);
        } catch (\Throwable $e) {
            Log::warning('Employee photo delete failed.', [
                'employee_id' => (int) $employee->employee_id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Photo delete failed. Please try again.')
                ->setStatusCode(303);
        }
    }
}
