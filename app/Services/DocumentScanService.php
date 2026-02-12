<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

class DocumentScanService
{
    /**
     * @param array<int, UploadedFile> $files
     * @return array{first_name:?string,last_name:?string,email:?string,mobile_number:?string,_meta:array}
     */
    public function scanForEmployeeFields(array $files): array
    {
        $errors = [];
        $meta = [
            'scanned_files' => 0,
            'files_with_text' => 0,
            'empty_text_files' => 0,
            'errors' => [],
        ];

        $result = [
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'mobile_number' => null,
            '_meta' => $meta,
        ];

        foreach ($files as $file) {
            $result['_meta']['scanned_files']++;

            $text = $this->extractText($file, $errors);
            if ($text === '') {
                $result['_meta']['empty_text_files']++;
                continue;
            }

            $result['_meta']['files_with_text']++;

            if (!$result['email']) {
                $result['email'] = $this->extractEmail($text);
            }

            if (!$result['mobile_number']) {
                $result['mobile_number'] = $this->extractMobileNumber($text);
            }

            if (!$result['first_name'] || !$result['last_name']) {
                $nameParts = $this->extractNameParts($text);
                $result['first_name'] = $result['first_name'] ?? $nameParts['first_name'];
                $result['last_name'] = $result['last_name'] ?? $nameParts['last_name'];
            }

            $done = $result['email'] && $result['mobile_number'] && $result['first_name'] && $result['last_name'];
            if ($done) {
                break;
            }
        }

        if (!empty($errors)) {
            // De-duplicate and clamp.
            $errors = array_values(array_unique(array_filter(array_map('strval', $errors))));
            $result['_meta']['errors'] = array_slice($errors, 0, 10);
        }

        return $result;
    }

    private function extractText(UploadedFile $file, array &$errors): string
    {
        $mime = (string) $file->getMimeType();

        if (str_contains($mime, 'pdf')) {
            return $this->extractTextFromPdf($file, $errors);
        }

        if (str_starts_with($mime, 'image/')) {
            return $this->extractTextFromImage($file, $errors);
        }

        return '';
    }

    private function extractTextFromPdf(UploadedFile $file, array &$errors): string
    {
        $bin = (string) config('crewly.scan.pdftotext_path', 'pdftotext');
        $timeout = (int) config('crewly.scan.timeout_seconds', 25);
        $maxBytes = (int) config('crewly.scan.max_text_bytes', 200_000);

        $inputPath = $file->getRealPath();
        if (!$inputPath) {
            return '';
        }

        $outPath = tempnam(sys_get_temp_dir(), 'crewly_pdftxt_');
        if (!$outPath) {
            return '';
        }

        try {
            // Use an output file for better Windows compatibility.
            $process = new Process([$bin, '-layout', '-nopgbrk', '-enc', 'UTF-8', $inputPath, $outPath]);
            $process->setTimeout($timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                $msg = trim((string) ($process->getErrorOutput() ?: $process->getOutput()));
                $errors[] = $this->formatToolFailure('pdftotext', $msg);
                return '';
            }

            $text = @file_get_contents($outPath);
            if (!is_string($text)) {
                return '';
            }

            return $this->normalizeAndClampText($text, $maxBytes);
        } finally {
            @unlink($outPath);
        }
    }

    private function extractTextFromImage(UploadedFile $file, array &$errors): string
    {
        $bin = (string) config('crewly.scan.tesseract_path', 'tesseract');
        $timeout = (int) config('crewly.scan.timeout_seconds', 25);
        $maxBytes = (int) config('crewly.scan.max_text_bytes', 200_000);

        $inputPath = $file->getRealPath();
        if (!$inputPath) {
            return '';
        }

        // tesseract <image> stdout
        $process = new Process([$bin, $inputPath, 'stdout']);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            $msg = trim((string) ($process->getErrorOutput() ?: $process->getOutput()));
            $errors[] = $this->formatToolFailure('tesseract', $msg);
            return '';
        }

        return $this->normalizeAndClampText((string) $process->getOutput(), $maxBytes);
    }

    private function normalizeAndClampText(string $text, int $maxBytes): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (strlen($text) <= $maxBytes) {
            return $text;
        }

        return substr($text, 0, $maxBytes);
    }

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            return strtolower($m[0]);
        }

        return null;
    }

    private function extractMobileNumber(string $text): ?string
    {
        // Loose match: +63, 09xx, or general digit sequences with separators.
        if (!preg_match('/(\+?\d[\d\s().\-]{7,}\d)/', $text, $m)) {
            return null;
        }

        $raw = trim($m[1]);
        $normalized = preg_replace('/[^0-9+]/', '', $raw) ?? $raw;
        $normalized = ltrim($normalized);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array{first_name:?string,last_name:?string}
     */
    private function extractNameParts(string $text): array
    {
        $first = $this->extractLabeledValue($text, ['First Name', 'Firstname']);
        $last = $this->extractLabeledValue($text, ['Last Name', 'Lastname', 'Surname']);

        if ($first || $last) {
            return [
                'first_name' => $first ? $this->cleanName($first) : null,
                'last_name' => $last ? $this->cleanName($last) : null,
            ];
        }

        $full = $this->extractLabeledValue($text, ['Employee Name', 'Name']);
        $full = $full ? $this->cleanName($full) : null;
        if (!$full) {
            return ['first_name' => null, 'last_name' => null];
        }

        $parts = preg_split('/\s+/', $full) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        if (count($parts) < 2) {
            return ['first_name' => $parts[0] ?? null, 'last_name' => null];
        }

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[count($parts) - 1],
        ];
    }

    /**
     * @param array<int, string> $labels
     */
    private function extractLabeledValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $pattern = '/\b' . preg_quote($label, '/') . '\b\s*[:\-]\s*(.+)$/im';
            if (!preg_match($pattern, $text, $m)) {
                continue;
            }

            $value = trim((string) ($m[1] ?? ''));
            if ($value === '') {
                continue;
            }

            // Stop at common delimiters in same line.
            $value = preg_split('/\s{2,}|\t|\|/', $value)[0] ?? $value;
            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function cleanName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        // Keep letters/marks/spaces plus common punctuation. Use \x{2019} for the right single quotation mark.
        $value = preg_replace('/[^\p{L}\p{M}\s.\-\'\x{2019}]/u', '', $value) ?? $value;
        $value = trim($value, " .-\t\n\r\0\x0B");

        return $value;
    }

    private function formatToolFailure(string $tool, string $rawMessage): string
    {
        $rawMessage = trim($rawMessage);

        $notFoundIndicators = [
            'is not recognized as an internal or external command',
            'not recognized as an internal or external command',
            'no such file or directory',
            'could not open',
            'not found',
        ];

        $lower = strtolower($rawMessage);
        foreach ($notFoundIndicators as $indicator) {
            if ($indicator !== '' && str_contains($lower, strtolower($indicator))) {
                $envKey = $tool === 'pdftotext' ? 'CREWLY_PDFTOTEXT_PATH' : 'CREWLY_TESSERACT_PATH';
                return "$tool is not available on the server. Install it and/or set $envKey in .env.";
            }
        }

        if ($rawMessage === '') {
            return "$tool failed";
        }

        return "$tool failed: $rawMessage";
    }
}
