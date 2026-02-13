<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicantDocumentService extends Service
{
    public function __construct(
        private readonly DocumentCryptoService $crypto,
        private readonly ActivityLogService $activityLogService,
    )
    {
    }

    /**
     * @param array{type:string, notes?:?string} $validated
     * @param array<int, UploadedFile> $files
     *
     * @return array<int, ApplicantDocument>
     */
    public function uploadMany(Applicant $applicant, array $validated, array $files, ?int $uploadedBy): array
    {
        return DB::transaction(function () use ($applicant, $validated, $files, $uploadedBy) {
            $created = [];
            $storedPaths = [];
            $totalSize = 0;

            try {
                foreach ($files as $file) {
                    $uuid = (string) Str::uuid();
                    $path = "recruitment/applicants/{$applicant->id}/documents/{$uuid}.bin";

                    $stored = $this->crypto->encryptAndStore($file, $path);
                    $storedPaths[] = $stored['file_path'];
                    $totalSize += (int) ($stored['file_size'] ?? 0);

                    $created[] = ApplicantDocument::query()->create([
                        'applicant_id' => (int) $applicant->id,
                        'type' => $validated['type'],
                        'original_name' => $stored['original_name'],
                        'file_path' => $stored['file_path'],
                        'mime_type' => $stored['mime_type'],
                        'file_size' => $stored['file_size'],
                        'notes' => $validated['notes'] ?? null,
                        'uploaded_by' => $uploadedBy,
                        'is_encrypted' => true,
                        'encryption_algo' => $stored['algo'],
                        'encryption_iv' => $stored['iv'],
                        'encryption_tag' => $stored['tag'],
                        'key_version' => $stored['key_version'],
                    ]);
                }

                $applicant->forceFill([
                    'last_activity_at' => now(),
                ])->save();

                $this->activityLogService->log('documents_uploaded', $applicant, [
                    'type' => (string) $validated['type'],
                    'count' => count($created),
                    'total_size' => $totalSize,
                    'document_ids' => array_map(fn (ApplicantDocument $d) => (int) $d->id, $created),
                ], 'Applicant documents have been uploaded.');

                app(\App\Services\AuditLogger::class)->log(
                    'applicant_document.uploaded',
                    $applicant,
                    [],
                    [
                        'applicant_id' => (int) $applicant->id,
                        'type' => (string) $validated['type'],
                        'count' => count($created),
                        'total_size' => $totalSize,
                        'documents' => array_map(fn (ApplicantDocument $d) => [
                            'id' => (int) $d->id,
                            'filename' => (string) ($d->original_name ?? ''),
                            'file_size' => (int) ($d->file_size ?? 0),
                            'mime_type' => (string) ($d->mime_type ?? ''),
                        ], $created),
                    ],
                    [],
                    'Applicant documents uploaded.'
                );

                return $created;
            } catch (\Throwable $e) {
                $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
                foreach ($storedPaths as $path) {
                    Storage::disk($disk)->delete($path);
                }

                throw $e;
            }
        });
    }

    public function download(Applicant $applicant, ApplicantDocument $document): StreamedResponse
    {
        if ((int) $document->applicant_id !== (int) $applicant->id) {
            abort(404);
        }

        if (!$document->is_encrypted) {
            abort(500, 'Document is not encrypted.');
        }

        $stream = $this->crypto->decryptToStream($document->file_path, $document->encryption_iv, $document->encryption_tag);
        $fileName = $document->original_name ?: 'document';
        $mime = $document->mime_type ?: 'application/octet-stream';

        $this->activityLogService->log('document_downloaded', $applicant, [
            'document_id' => (int) $document->id,
            'type' => (string) $document->type,
            'file_size' => (int) $document->file_size,
        ], 'Applicant document has been downloaded.');

        app(\App\Services\AuditLogger::class)->log(
            'document.downloaded',
            $document,
            [],
            [],
            [
                'module' => 'recruitment',
                'applicant_id' => (int) $applicant->id,
                'document_id' => (int) $document->id,
                'type' => (string) $document->type,
                'filename' => (string) ($document->original_name ?? ''),
                'file_size' => (int) ($document->file_size ?? 0),
                'mime_type' => (string) ($document->mime_type ?? ''),
            ],
            'Applicant document downloaded.'
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

    public function delete(Applicant $applicant, ApplicantDocument $document): void
    {
        if ((int) $document->applicant_id !== (int) $applicant->id) {
            abort(404);
        }

        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        $path = (string) ($document->file_path ?? '');

        DB::transaction(function () use ($applicant, $document) {
            $meta = [
                'document_id' => (int) $document->id,
                'type' => (string) $document->type,
                'file_size' => (int) $document->file_size,
            ];

            app(\App\Services\AuditLogger::class)->log(
                'applicant_document.deleted',
                $document,
                [
                    'applicant_id' => (int) $applicant->id,
                    'document_id' => (int) $document->id,
                    'type' => (string) $document->type,
                    'filename' => (string) ($document->original_name ?? ''),
                    'file_size' => (int) ($document->file_size ?? 0),
                ],
                [],
                ['module' => 'recruitment'],
                'Applicant document deleted.'
            );

            $document->delete();

            $applicant->forceFill([
                'last_activity_at' => now(),
            ])->save();

            $this->activityLogService->log('document_deleted', $applicant, $meta, 'Applicant document has been deleted.');
        });

        try {
            if ($path !== '') {
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Applicant document cleanup failed after delete.', [
                'applicant_id' => (int) $applicant->id,
                'document_id' => (int) $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
