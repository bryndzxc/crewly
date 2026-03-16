<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Government Contribution Monitor
    |--------------------------------------------------------------------------
    |
    | Configure the official sources Crewly checks for updates.
    | These URLs can point to an HTML page, a PDF, a JSON document, or a CSV.
    | For best results (and deterministic parsing), use JSON or CSV.
    |
    */

    'sources' => [
        'sss' => [
            'url' => env('GOV_MONITOR_SSS_URL', ''),
        ],
        'philhealth' => [
            'url' => env('GOV_MONITOR_PHILHEALTH_URL', ''),
        ],
        'pagibig' => [
            'url' => env('GOV_MONITOR_PAGIBIG_URL', ''),
        ],
    ],

    // Where raw snapshots are stored (under storage/app)
    'snapshot_disk' => env('GOV_MONITOR_SNAPSHOT_DISK', null), // null => default disk
    'snapshot_dir' => env('GOV_MONITOR_SNAPSHOT_DIR', 'government-updates'),

    // If the configured URL is an HTML page that links to a PDF circular,
    // prefer fetching the PDF and hashing/parsing it (more stable than HTML).
    'prefer_pdf_links' => env('GOV_MONITOR_PREFER_PDF_LINKS', true),

    // PDF text extraction (Poppler). If empty, uses `pdftotext` from PATH.
    // On Windows, set this to a full path like: C:\\poppler\\Library\\bin\\pdftotext.exe
    'pdftotext_path' => env('GOV_MONITOR_PDFTOTEXT_PATH', ''),
    'pdftotext_timeout' => (int) env('GOV_MONITOR_PDFTOTEXT_TIMEOUT', 60),

    // Safety: do not auto-activate new rules; monitor only creates drafts.
    'auto_activate' => false,
];
