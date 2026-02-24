<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemoPdfService extends Service
{
    /**
     * Render and store a memo PDF under storage/app/private/memos/{employee_id}/{uuid}.pdf
     */
    public function renderAndStore(int $employeeId, string $title, string $bodyHtml): array
    {
        $uuid = (string) Str::uuid();
        $relativePath = "private/memos/{$employeeId}/{$uuid}.pdf";

        $pdf = Pdf::loadView('pdf.memo', [
            'title' => $title,
            'bodyHtml' => $bodyHtml,
            'generatedAt' => now(),
        ])->setPaper('A4');

        Storage::disk('local')->put($relativePath, $pdf->output());

        return [
            'pdf_path' => $relativePath,
            'filename' => $this->safeFilename($title) . '.pdf',
        ];
    }

    private function safeFilename(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'memo';
        }

        return Str::limit($base, 80, '');
    }
}
