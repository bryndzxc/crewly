<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocumentService extends Service
{
    public function __construct(private readonly DocumentCryptoService $crypto)
    {
    }

    /**
     * @param array<int, array{file: UploadedFile, type: string, issue_date?:?string, expiry_date?:?string, notes?:?string}> $items
     * @param array{issue_date?:?string, expiry_date?:?string, notes?:?string} $shared
     *
     * @return array<int, EmployeeDocument>
     */
    public function uploadItems(Employee $employee, array $items, array $shared, ?int $uploadedBy): array
    {
        return DB::transaction(function () use ($employee, $items, $shared, $uploadedBy) {
            $created = [];
            $storedPaths = [];

            try {
                foreach ($items as $item) {
                    $file = $item['file'] ?? null;
                    if (!$file instanceof UploadedFile) {
                        throw new \InvalidArgumentException('Invalid document file.');
                    }

                    $type = (string) ($item['type'] ?? 'Document');
                    $type = trim($type) === '' ? 'Document' : $type;

                    $issueDate = $item['issue_date'] ?? ($shared['issue_date'] ?? null);
                    $expiryDate = $item['expiry_date'] ?? ($shared['expiry_date'] ?? null);
                    $notes = $item['notes'] ?? ($shared['notes'] ?? null);

                    $uuid = (string) Str::uuid();
                    $path = "employees/{$employee->employee_id}/documents/{$uuid}.bin";

                    $stored = $this->crypto->encryptAndStore($file, $path);
                    $storedPaths[] = $stored['file_path'];

                    $created[] = EmployeeDocument::query()->create([
                        'employee_id' => (int) $employee->employee_id,
                        'type' => $type,
                        'original_name' => $stored['original_name'],
                        'file_path' => $stored['file_path'],
                        'mime_type' => $stored['mime_type'],
                        'file_size' => $stored['file_size'],
                        'issue_date' => $issueDate,
                        'expiry_date' => $expiryDate,
                        'notes' => $notes,
                        'uploaded_by' => $uploadedBy,
                        'is_encrypted' => true,
                        'encryption_algo' => $stored['algo'],
                        'encryption_iv' => $stored['iv'],
                        'encryption_tag' => $stored['tag'],
                        'key_version' => $stored['key_version'],
                    ]);
                }

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

    /**
     * @param array{type:string, issue_date?:?string, expiry_date?:?string, notes?:?string} $validated
     * @param array<int, UploadedFile> $files
     *
     * @return array<int, EmployeeDocument>
     */
    public function uploadMany(Employee $employee, array $validated, array $files, ?int $uploadedBy): array
    {
        return DB::transaction(function () use ($employee, $validated, $files, $uploadedBy) {
            $created = [];
            $storedPaths = [];

            try {
                foreach ($files as $file) {
                    $uuid = (string) Str::uuid();
                    $path = "employees/{$employee->employee_id}/documents/{$uuid}.bin";

                    $stored = $this->crypto->encryptAndStore($file, $path);
                    $storedPaths[] = $stored['file_path'];

                    $created[] = EmployeeDocument::query()->create([
                        'employee_id' => (int) $employee->employee_id,
                        'type' => $validated['type'],
                        'original_name' => $stored['original_name'],
                        'file_path' => $stored['file_path'],
                        'mime_type' => $stored['mime_type'],
                        'file_size' => $stored['file_size'],
                        'issue_date' => $validated['issue_date'] ?? null,
                        'expiry_date' => $validated['expiry_date'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'uploaded_by' => $uploadedBy,
                        'is_encrypted' => true,
                        'encryption_algo' => $stored['algo'],
                        'encryption_iv' => $stored['iv'],
                        'encryption_tag' => $stored['tag'],
                        'key_version' => $stored['key_version'],
                    ]);
                }

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
}
