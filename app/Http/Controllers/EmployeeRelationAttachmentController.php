<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRelationAttachmentRequest;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\EmployeeRelationAttachment;
use App\Services\DocumentCryptoService;
use App\Services\EmployeeRelationService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeRelationAttachmentController extends Controller
{
    public function __construct(
        private readonly DocumentCryptoService $crypto,
        private readonly EmployeeRelationService $employeeRelationService,
    ) {
    }

    public function store(StoreEmployeeRelationAttachmentRequest $request, string $attachableType, int $id): RedirectResponse
    {
        $attachable = $this->resolveAttachable($attachableType, $id);

        $validated = $request->validated();
        $files = $request->file('files', []);
        $files = is_array($files) ? $files : [];
        $type = $validated['type'] ?? null;

        $this->employeeRelationService->addAttachments($attachable, $files, $type, $request->user()?->id);

        return redirect()->route('employees.show', (int) $attachable->employee_id)
            ->with('success', 'Attachment(s) uploaded successfully.');
    }

    public function download(EmployeeRelationAttachment $attachment): StreamedResponse
    {
        $attachable = $attachment->attachable;
        if (!($attachable instanceof EmployeeNote) && !($attachable instanceof EmployeeIncident)) {
            abort(404);
        }

        if (!$attachment->is_encrypted) {
            abort(500, 'Attachment is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream($attachment->file_path, $attachment->encryption_iv, $attachment->encryption_tag);
        $fileName = $attachment->original_name ?: 'attachment';
        $mime = $attachment->mime_type ?: 'application/octet-stream';

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

    public function destroy(EmployeeRelationAttachment $attachment): RedirectResponse
    {
        $attachable = $attachment->attachable;
        if (!($attachable instanceof EmployeeNote) && !($attachable instanceof EmployeeIncident)) {
            abort(404);
        }

        $employeeId = (int) $attachable->employee_id;

        $this->employeeRelationService->deleteAttachment($attachment, $attachable);

        return redirect()->route('employees.show', $employeeId)
            ->with('success', 'Attachment deleted successfully.');
    }

    private function resolveAttachable(string $type, int $id): EmployeeNote|EmployeeIncident
    {
        return match ($type) {
            'notes' => EmployeeNote::query()->findOrFail($id),
            'incidents' => EmployeeIncident::query()->findOrFail($id),
            default => abort(404),
        };
    }
}
