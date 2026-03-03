<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRelationAttachment;
use App\Models\Feedback;
use App\Services\EmployeeRelationAttachmentService;
use App\Services\DocumentCryptoService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackAttachmentController extends Controller
{
    public function __construct(
        private readonly DocumentCryptoService $crypto,
        private readonly EmployeeRelationAttachmentService $attachments,
    )
    {
    }

    public function download(int $attachment): StreamedResponse
    {
        /** @var EmployeeRelationAttachment $record */
        $record = EmployeeRelationAttachment::withoutCompanyScope()->findOrFail($attachment);

        $attachable = $record->attachable;
        if (!($attachable instanceof Feedback)) {
            abort(404);
        }

        if (!$record->is_encrypted) {
            abort(500, 'Attachment is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream($record->file_path, $record->encryption_iv, $record->encryption_tag);
        $fileName = $record->original_name ?: 'attachment';
        $mime = $record->mime_type ?: 'application/octet-stream';

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

    public function view(int $attachment): StreamedResponse
    {
        /** @var EmployeeRelationAttachment $record */
        $record = EmployeeRelationAttachment::withoutCompanyScope()->findOrFail($attachment);

        $attachable = $record->attachable;
        if (!($attachable instanceof Feedback)) {
            abort(404);
        }

        if (!$record->is_encrypted) {
            abort(500, 'Attachment is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream($record->file_path, $record->encryption_iv, $record->encryption_tag);
        $fileName = $record->original_name ?: 'attachment';
        $mime = $record->mime_type ?: 'application/octet-stream';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, Response::HTTP_OK, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(int $attachment): Response
    {
        /** @var EmployeeRelationAttachment $record */
        $record = EmployeeRelationAttachment::withoutCompanyScope()->findOrFail($attachment);

        $attachable = $record->attachable;
        if (!($attachable instanceof Feedback)) {
            abort(404);
        }

        $this->attachments->delete($record);

        return response()->noContent();
    }
}
