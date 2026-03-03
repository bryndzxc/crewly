<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Services\DocumentCryptoService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashAdvanceAttachmentController extends Controller
{
    public function __construct(private readonly DocumentCryptoService $crypto) {}

    public function download(CashAdvance $cashAdvance): StreamedResponse
    {
        $this->authorize('downloadAttachment', $cashAdvance);

        if (!$cashAdvance->has_attachment) {
            abort(404);
        }

        if (!$cashAdvance->attachment_is_encrypted) {
            abort(500, 'Attachment is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream(
            (string) $cashAdvance->attachment_path,
            (string) ($cashAdvance->attachment_encryption_iv ?? ''),
            (string) ($cashAdvance->attachment_encryption_tag ?? '')
        );

        $fileName = $cashAdvance->attachment_original_name ?: 'attachment';
        $mime = $cashAdvance->attachment_mime_type ?: 'application/octet-stream';

        app(\App\Services\AuditLogger::class)->log(
            'cash_advance.attachment.downloaded',
            $cashAdvance,
            [],
            [],
            [
                'cash_advance_id' => (int) $cashAdvance->id,
                'employee_id' => (int) $cashAdvance->employee_id,
                'filename' => (string) ($cashAdvance->attachment_original_name ?? ''),
                'file_size' => (int) ($cashAdvance->attachment_size ?? 0),
                'mime_type' => (string) ($cashAdvance->attachment_mime_type ?? ''),
            ],
            'Cash advance attachment downloaded.'
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
}
