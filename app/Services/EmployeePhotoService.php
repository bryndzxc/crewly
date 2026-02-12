<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class EmployeePhotoService extends Service
{
    public function __construct(private readonly DocumentCryptoService $crypto)
    {
    }

    public function setPhoto(Employee $employee, UploadedFile $file): void
    {
        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));

        $uuid = (string) Str::uuid();
        $path = "employees/{$employee->employee_id}/photo/{$uuid}.bin";

        $uploadForEncryption = $file;
        $tempPath = null;

        try {
            $optimized = $this->optimizeToJpegTempFile($file, 512, 40 * 1024);
            if ($optimized) {
                $tempPath = $optimized['path'];
                $uploadForEncryption = new SymfonyUploadedFile(
                    $tempPath,
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg',
                    'image/jpeg',
                    null,
                    true
                );
            }

            $stored = $this->crypto->encryptAndStore($uploadForEncryption, $path);
        } finally {
            if (is_string($tempPath) && $tempPath !== '' && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        $oldPath = (string) ($employee->getAttribute('photo_path') ?? '');
        if ($oldPath !== '') {
            Storage::disk($disk)->delete($oldPath);
        }

        $employee->forceFill([
            'photo_path' => $stored['file_path'],
            'photo_original_name' => $stored['original_name'],
            'photo_mime_type' => $stored['mime_type'] ?? 'image/jpeg',
            'photo_size' => $stored['file_size'],
            'photo_is_encrypted' => true,
            'photo_encryption_algo' => $stored['algo'],
            'photo_encryption_iv' => $stored['iv'],
            'photo_encryption_tag' => $stored['tag'],
            'photo_key_version' => $stored['key_version'],
        ])->save();
    }

    /**
     * Returns a temp file path for a JPEG-optimized version of the uploaded image.
     * Uses GD if available; returns null if optimization is not possible.
     *
     * @return array{path:string}|null
     */
    private function optimizeToJpegTempFile(UploadedFile $file, int $maxDim, int $targetBytes): ?array
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            return null;
        }

        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            return null;
        }

        $mime = (string) ($file->getClientMimeType() ?? '');

        $src = null;
        try {
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                if (!function_exists('imagecreatefromjpeg')) return null;
                $src = @imagecreatefromjpeg($realPath);
            } elseif ($mime === 'image/png') {
                if (!function_exists('imagecreatefrompng')) return null;
                $src = @imagecreatefrompng($realPath);
            } else {
                return null;
            }

            if (!$src) {
                return null;
            }

            $w = imagesx($src);
            $h = imagesy($src);
            if (!$w || !$h) {
                return null;
            }

            $scale = min(1, $maxDim / max($w, $h));
            $newW = max(1, (int) floor($w * $scale));
            $newH = max(1, (int) floor($h * $scale));

            $dst = imagecreatetruecolor($newW, $newH);
            if (!$dst) {
                return null;
            }

            // Fill with white to avoid black background when source has transparency.
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

            $jpegBytes = $this->encodeJpegToTarget($dst, $targetBytes);
            imagedestroy($dst);

            if ($jpegBytes === null) {
                return null;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'crewly_photo_');
            if (!is_string($tmp) || $tmp === '') {
                return null;
            }

            file_put_contents($tmp, $jpegBytes);

            return ['path' => $tmp];
        } finally {
            if (is_resource($src) || $src instanceof \GdImage) {
                @imagedestroy($src);
            }
        }
    }

    private function encodeJpegToTarget($gd, int $targetBytes): ?string
    {
        // Try a few quality levels until we reach target.
        $qualities = [82, 76, 70, 64, 58, 52, 46];
        $best = null;

        foreach ($qualities as $q) {
            ob_start();
            imagejpeg($gd, null, $q);
            $bytes = ob_get_clean();
            if (!is_string($bytes)) {
                continue;
            }

            $best = $bytes;
            if (strlen($bytes) <= $targetBytes) {
                return $bytes;
            }
        }

        // Still too large: return smallest we got (better than failing).
        return $best;
    }
}
