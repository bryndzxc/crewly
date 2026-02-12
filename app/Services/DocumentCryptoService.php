<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentCryptoService
{
    /**
     * Encrypts file contents and stores encrypted bytes to Storage.
     *
     * @return array{file_path:string, original_name:string, mime_type:?string, file_size:?int, iv:string, tag:string, algo:string, key_version:int}
     */
    public function encryptAndStore(UploadedFile $file, string $path): array
    {
        $plaintext = @file_get_contents($file->getRealPath());
        if ($plaintext === false) {
            throw new RuntimeException('Unable to read uploaded file contents.');
        }

        $cipher = (string) config('crewly.encryption.cipher', 'aes-256-gcm');
        $algo = (string) config('crewly.encryption.algo', 'AES-256-GCM');
        $keyVersion = (int) config('crewly.encryption.key_version', 1);
        $key = $this->resolveKeyBytes();

        $ivLen = openssl_cipher_iv_length($cipher);
        if (!is_int($ivLen) || $ivLen <= 0) {
            throw new RuntimeException('Invalid cipher IV length.');
        }

        $iv = random_bytes($ivLen);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false || $tag === '') {
            throw new RuntimeException('Encryption failed.');
        }

        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        $stored = Storage::disk($disk)->put($path, $ciphertext, ['visibility' => 'private']);
        if (!$stored) {
            throw new RuntimeException('Failed to store encrypted document.');
        }

        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'algo' => $algo,
            'key_version' => $keyVersion,
        ];
    }

    /**
     * Decrypts encrypted bytes to an in-memory stream.
     *
     * @return resource
     */
    public function decryptToStream(string $filePath, string $iv, string $tag)
    {
        $cipher = (string) config('crewly.encryption.cipher', 'aes-256-gcm');
        $key = $this->resolveKeyBytes();

        $ivBytes = base64_decode($iv, true);
        $tagBytes = base64_decode($tag, true);

        if ($ivBytes === false || $tagBytes === false) {
            throw new RuntimeException('Invalid encryption metadata (iv/tag).');
        }

        $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
        $ciphertext = Storage::disk($disk)->get($filePath);
        if ($ciphertext === null) {
            throw new RuntimeException('Encrypted document not found.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $ivBytes,
            $tagBytes
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed.');
        }

        // Keep plaintext in memory only. We cap uploads to 10MB, so this stays safe.
        $handle = fopen('php://temp/maxmemory:12582912', 'w+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to create stream.');
        }

        fwrite($handle, $plaintext);
        rewind($handle);

        return $handle;
    }

    private function resolveKeyBytes(): string
    {
        $raw = (string) (config('crewly.encryption.key') ?: config('app.key'));
        $raw = trim($raw);
        if ($raw === '') {
            throw new RuntimeException('Encryption key is not configured.');
        }

        $decoded = $this->decodeKey($raw);
        if (strlen($decoded) !== 32) {
            throw new RuntimeException('Encryption key must be 32 bytes for AES-256-GCM.');
        }

        return $decoded;
    }

    private function decodeKey(string $key): string
    {
        $trimmed = trim($key);

        if (str_starts_with($trimmed, 'base64:')) {
            $b64 = substr($trimmed, 7);
            $decoded = base64_decode($b64, true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 encryption key.');
            }

            return $decoded;
        }

        // Allow raw base64 without prefix.
        $decoded = base64_decode($trimmed, true);
        if ($decoded !== false) {
            return $decoded;
        }

        // Last resort: treat as raw bytes (not recommended).
        return $trimmed;
    }
}
