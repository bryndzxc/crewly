<?php

namespace App\Http\Controllers;

use App\Models\Employee;
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

        $stream = $this->crypto->decryptToStream(
            $path,
            (string) ($employee->getAttribute('photo_encryption_iv') ?? ''),
            (string) ($employee->getAttribute('photo_encryption_tag') ?? '')
        );

        $mime = (string) ($employee->getAttribute('photo_mime_type') ?? 'application/octet-stream');

        return response()->stream(function () use ($stream) {
            if (is_resource($stream)) {
                rewind($stream);
                fpassthru($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png'],
        ]);

        try {
            $this->photos->setPhoto($employee, $validated['photo']);
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
        try {
            $this->photos->deletePhoto($employee);
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
