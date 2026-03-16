<?php

namespace App\Services\GovernmentParsers;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;

class SssParser implements \App\Services\GovernmentParsers\GovernmentParser
{
    public function parse(string $rawContent, array $context): array
    {
        $content = trim($rawContent);
        if ($content === '') {
            throw new InvalidArgumentException('Empty content.');
        }

        // Preferred formats: JSON array of rows OR CSV matching Crewly's SSS columns.
        if ($this->looksLikeJson($content)) {
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('Invalid JSON for SSS payload.');
            }
            // Ensure array of rows.
            $rows = array_values($decoded);
            if ($rows === [] || !is_array($rows[0] ?? null)) {
                throw new InvalidArgumentException('SSS JSON must be an array of objects/rows.');
            }
            return $rows;
        }

        if ($this->looksLikeCsv($content)) {
            return $this->parseCsv($content);
        }

        if ($this->looksLikeHtml($content)) {
            return $this->parseHtmlTable($content, $context);
        }

        // PDF extracted text or plain text fallback.
        return $this->parseFromText($content, $context);
    }

    private function looksLikeJson(string $content): bool
    {
        $c = ltrim($content);
        return str_starts_with($c, '[') || str_starts_with($c, '{');
    }

    private function looksLikeCsv(string $content): bool
    {
        return str_contains($content, ',') && str_contains($content, "\n");
    }

    private function looksLikeHtml(string $content): bool
    {
        $c = ltrim($content);
        return str_starts_with($c, '<') && (str_contains(substr($c, 0, 2000), '<table') || str_contains(substr($c, 0, 2000), '<html'));
    }

    /**
     * Parse the first plausible HTML table on the SSS page.
     *
     * @return array<int,array<string,mixed>>
     */
    private function parseHtmlTable(string $html, array $context): array
    {
        $effectiveFrom = $this->parseEffectiveFrom($this->normalizeText($this->stripHtmlToText($html)), $context);

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $tables = $xpath->query('//table');
        if (! $tables || $tables->length === 0) {
            throw new InvalidArgumentException('SSS HTML source did not contain a table.');
        }

        $bestRows = [];

        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            if (! $rows || $rows->length < 2) {
                continue;
            }

            // Find header row with useful labels.
            $headerCells = null;
            foreach ($rows as $row) {
                $ths = $xpath->query('.//th', $row);
                if ($ths && $ths->length > 0) {
                    $headerCells = [];
                    foreach ($ths as $th) {
                        $headerCells[] = $this->normalizeHeader($th->textContent);
                    }
                    break;
                }
            }

            if (! is_array($headerCells) || count($headerCells) < 4) {
                continue;
            }

            // Heuristic: must mention employee+employer OR monthly salary credit.
            $headerBlob = implode(' ', $headerCells);
            if (! (str_contains($headerBlob, 'employee') && str_contains($headerBlob, 'employer')) && ! str_contains($headerBlob, 'monthly salary credit')) {
                continue;
            }

            $parsed = $this->parseRowsFromHtmlTable($xpath, $table, $effectiveFrom);
            if (count($parsed) > count($bestRows)) {
                $bestRows = $parsed;
            }
        }

        if (count($bestRows) === 0) {
            throw new InvalidArgumentException('Unable to parse SSS contribution table from HTML.');
        }

        return $bestRows;
    }

    /** @return array<int,array<string,mixed>> */
    private function parseRowsFromHtmlTable(DOMXPath $xpath, $table, string $effectiveFrom): array
    {
        $rows = $xpath->query('.//tr', $table);
        if (! $rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('./th|./td', $row);
            if (! $cells || $cells->length < 4) {
                continue;
            }

            $values = [];
            foreach ($cells as $cell) {
                $values[] = $this->normalizeCell($cell->textContent);
            }

            // Skip header-like rows.
            $blob = strtolower(implode(' ', $values));
            if (str_contains($blob, 'range') || str_contains($blob, 'employee') || str_contains($blob, 'employer') || str_contains($blob, 'monthly salary credit')) {
                continue;
            }

            // Heuristic mapping: first two numeric columns as range_from/range_to.
            $nums = [];
            foreach ($values as $v) {
                $n = $this->toNumberOrNull($v);
                if ($n !== null) {
                    $nums[] = $n;
                }
            }

            if (count($nums) < 4) {
                continue;
            }

            $rangeFrom = $nums[0];
            $rangeTo = $nums[1];
            $msc = $nums[2];

            // Remaining numbers: try to pick employee/employer/ec as last 3.
            $employee = $nums[count($nums) - 3] ?? null;
            $employer = $nums[count($nums) - 2] ?? null;
            $ec = $nums[count($nums) - 1] ?? null;

            if ($rangeFrom === null || $rangeTo === null || $employee === null || $employer === null) {
                continue;
            }

            $out[] = [
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'range_from' => $rangeFrom,
                'range_to' => $rangeTo,
                'monthly_salary_credit' => $msc,
                'employee_share' => $employee,
                'employer_share' => $employer,
                'ec_share' => $ec,
                'notes' => 'Parsed from HTML source',
            ];
        }

        return $out;
    }

    /**
     * Parse SSS from extracted PDF/plain text.
     *
     * This is best-effort; for maximum reliability prefer a JSON/CSV source.
     *
     * @return array<int,array<string,mixed>>
     */
    private function parseFromText(string $content, array $context): array
    {
        $text = $this->normalizeText($content);
        $effectiveFrom = $this->parseEffectiveFrom($text, $context);

        // Look for lines with at least 5 numbers (range_from, range_to, msc, employee, employer, [ec]).
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $rows = [];

        foreach ($lines as $line) {
            $l = trim((string) $line);
            if ($l === '') {
                continue;
            }

            // Extract numbers like 1,234.56
            if (! preg_match_all('/\d{1,3}(?:,\d{3})*(?:\.\d+)?/', $l, $m)) {
                continue;
            }

            $nums = array_map(fn ($x) => (float) str_replace(',', '', (string) $x), $m[0]);
            if (count($nums) < 5) {
                continue;
            }

            $rangeFrom = $nums[0];
            $rangeTo = $nums[1];
            $msc = $nums[2] ?? null;
            $employee = $nums[count($nums) - 3] ?? null;
            $employer = $nums[count($nums) - 2] ?? null;
            $ec = $nums[count($nums) - 1] ?? null;

            $rows[] = [
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'range_from' => $rangeFrom,
                'range_to' => $rangeTo,
                'monthly_salary_credit' => $msc,
                'employee_share' => $employee,
                'employer_share' => $employer,
                'ec_share' => $ec,
                'notes' => 'Parsed from PDF/text source',
            ];
        }

        if (count($rows) === 0) {
            throw new InvalidArgumentException('Unable to parse SSS table from PDF/text. Consider pointing the monitor to an HTML table page or providing a CSV/JSON source.');
        }

        return $rows;
    }

    private function parseCsv(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));
        if (count($lines) < 2) {
            throw new InvalidArgumentException('SSS CSV must include a header and at least one row.');
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($v) => trim((string) $v), $header);
        $index = array_flip($header);

        $required = [
            'effective_from',
            'effective_to',
            'range_from',
            'range_to',
            'monthly_salary_credit',
            'employee_share',
            'employer_share',
            'ec_share',
            'notes',
        ];

        foreach ($required as $col) {
            if (!array_key_exists($col, $index)) {
                throw new InvalidArgumentException("SSS CSV missing required column '{$col}'.");
            }
        }

        $rows = [];
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $get = fn (string $col) => $row[$index[$col]] ?? null;

            $rows[] = [
                'effective_from' => $this->toStringOrNull($get('effective_from')),
                'effective_to' => $this->toStringOrNull($get('effective_to')),
                'range_from' => $this->toNumberOrNull($get('range_from')),
                'range_to' => $this->toNumberOrNull($get('range_to')),
                'monthly_salary_credit' => $this->toNumberOrNull($get('monthly_salary_credit')),
                'employee_share' => $this->toNumberOrNull($get('employee_share')),
                'employer_share' => $this->toNumberOrNull($get('employer_share')),
                'ec_share' => $this->toNumberOrNull($get('ec_share')),
                'notes' => $this->toStringOrNull($get('notes')),
            ];
        }

        return $rows;
    }

    private function toStringOrNull($value): ?string
    {
        $t = trim((string) ($value ?? ''));
        return $t === '' ? null : $t;
    }

    private function toNumberOrNull($value): ?float
    {
        $t = trim((string) ($value ?? ''));
        if ($t === '') {
            return null;
        }

        $t = preg_replace('/[\s,\x{20B1}]/u', '', $t) ?? $t;

        return (float) $t;
    }

    private function normalizeText(string $text): string
    {
        $t = str_replace(["\r\n", "\r"], "\n", $text);
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;
        return trim($t);
    }

    private function stripHtmlToText(string $html): string
    {
        $s = preg_replace('~<script\b[^>]*>.*?</script>~is', ' ', $html) ?? $html;
        $s = preg_replace('~<style\b[^>]*>.*?</style>~is', ' ', $s) ?? $s;
        $s = preg_replace('~<(br|/p|/div|/li|/tr|/h\d)>~i', "\n", $s) ?? $s;
        $text = strip_tags($s);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        return trim((string) $text);
    }

    private function parseEffectiveFrom(string $text, array $context): string
    {
        $months = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?(\d{1,2})\s+'.$months.'\s+(\d{4})/i', $text, $m)) {
            return date('Y-m-d', strtotime($m[1].' '.$m[2].' '.$m[3]));
        }

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?'.$months.'\s+(\d{4})/i', $text, $m)) {
            return date('Y-m-d', strtotime('1 '.$m[1].' '.$m[2]));
        }

        $detectedAt = (string) ($context['detected_at'] ?? '');
        return $detectedAt !== '' ? substr($detectedAt, 0, 10) : now()->toDateString();
    }

    private function normalizeHeader(string $text): string
    {
        $t = strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? $text));
        return $t;
    }

    private function normalizeCell(string $text): string
    {
        $t = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        return $t;
    }
}
