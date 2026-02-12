<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\DocumentCryptoService;
use Illuminate\Http\Request;

class EmployeePhotoController extends Controller
{
    public function __construct(private readonly DocumentCryptoService $crypto)
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
}
