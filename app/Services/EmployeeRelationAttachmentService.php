<?php

namespace App\Services;

use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\EmployeeRelationAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeRelationAttachmentService extends Service
{
    public function __construct(private readonly DocumentCryptoService $crypto)
    {
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, EmployeeRelationAttachment>
     */
    public function uploadMany(Model $attachable, array $files, ?string $type, ?int $uploadedBy): array
    {
        $folder = $this->folderForAttachable($attachable);
        $attachableId = (int) $attachable->getKey();

        return DB::transaction(function () use ($attachable, $files, $type, $uploadedBy, $folder, $attachableId) {
            $created = [];
            $storedPaths = [];

            try {
                foreach ($files as $file) {
                    if (!$file instanceof UploadedFile) {
                        throw new \InvalidArgumentException('Invalid attachment file.');
                    }

                    $uuid = (string) Str::uuid();
                    $path = "relations/{$folder}/{$attachableId}/{$uuid}.bin";

                    $stored = $this->crypto->encryptAndStore($file, $path);
                    $storedPaths[] = $stored['file_path'];

                    /** @var EmployeeRelationAttachment $attachment */
                    $attachment = $attachable->attachments()->create([
                        'type' => $type,
                        'original_name' => $stored['original_name'],
                        'file_path' => $stored['file_path'],
                        'mime_type' => $stored['mime_type'],
                        'file_size' => $stored['file_size'],
                        'uploaded_by' => $uploadedBy,
                        'is_encrypted' => true,
                        'encryption_algo' => $stored['algo'],
                        'encryption_iv' => $stored['iv'],
                        'encryption_tag' => $stored['tag'],
                        'key_version' => $stored['key_version'],
                    ]);

                    $created[] = $attachment;
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

    public function delete(EmployeeRelationAttachment $attachment): void
    {
        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        Storage::disk($disk)->delete($attachment->file_path);
        $attachment->delete();
    }

    private function folderForAttachable(Model $attachable): string
    {
        return match (true) {
            $attachable instanceof EmployeeNote => 'note',
            $attachable instanceof EmployeeIncident => 'incident',
            default => throw new \InvalidArgumentException('Unsupported attachable type.'),
        };
    }
}
