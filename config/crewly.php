<?php

return [
    'documents' => [
        'disk' => env('CREWLY_DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
        'expiring_soon_days' => (int) env('CREWLY_DOCS_EXPIRING_SOON_DAYS', 30),
    ],

    'employees' => [
        // Comma-separated list of statuses that should be considered for
        // probation/regularization tracking.
        // Crewly currently ships with enum statuses like "Active"; if you add
        // "Probation" later, set: CREWLY_PROBATION_STATUSES="Probation,Active".
        'probation_statuses' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('CREWLY_PROBATION_STATUSES', 'Active')))
        )),
    ],

    'scan' => [
        // Optional absolute path to binaries (useful on Windows).
        // Examples:
        //   CREWLY_TESSERACT_PATH="C:\\Program Files\\Tesseract-OCR\\tesseract.exe"
        //   CREWLY_PDFTOTEXT_PATH="C:\\poppler\\Library\\bin\\pdftotext.exe"
        'tesseract_path' => env('CREWLY_TESSERACT_PATH', 'tesseract'),
        'pdftotext_path' => env('CREWLY_PDFTOTEXT_PATH', 'pdftotext'),

        // Hard limits to keep scans quick and safe.
        'max_files' => (int) env('CREWLY_SCAN_MAX_FILES', 3),
        'timeout_seconds' => (int) env('CREWLY_SCAN_TIMEOUT_SECONDS', 25),
        'max_text_bytes' => (int) env('CREWLY_SCAN_MAX_TEXT_BYTES', 200_000),
    ],

    'encryption' => [
        // Preferred: provide a dedicated 32-byte key (base64) via ENCRYPTION_KEY.
        // If not set, the app key will be used as a fallback.
        'key' => env('ENCRYPTION_KEY', null),
        'key_version' => (int) env('ENCRYPTION_KEY_VERSION', 1),
        'algo' => env('ENCRYPTION_ALGO', 'AES-256-GCM'),
        'cipher' => env('ENCRYPTION_CIPHER', 'aes-256-gcm'),
    ],
];
