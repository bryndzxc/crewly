<?php

namespace App\Services\GovernmentParsers;

use InvalidArgumentException;

class PagibigParser implements GovernmentParser
{
    public function parse(string $rawContent, array $context): array
    {
        $content = trim($rawContent);
        if ($content === '') {
            throw new InvalidArgumentException('Empty content.');
        }

        if ($this->looksLikeJson($content)) {
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('Invalid JSON for Pag-IBIG payload.');
            }

            if (array_is_list($decoded)) {
                $decoded = $decoded[0] ?? null;
                if (!is_array($decoded)) {
                    throw new InvalidArgumentException('Pag-IBIG JSON must be an object or an array with one object.');
                }
            }

            return $decoded;
        }

        if ($this->looksLikeCsv($content)) {
            return $this->parseSingleRowCsv($content, [
                'effective_from',
                'effective_to',
                'employee_rate_below_threshold',
                'employee_rate_above_threshold',
                'employer_rate',
                'salary_threshold',
                'monthly_cap',
                'notes',
            ]);
        }

        return $this->parseFromText($content, $context);
    }

    /** @return array<string,mixed> */
    private function parseFromText(string $content, array $context): array
    {
        $text = $this->normalizeText($content);

        $detectedAt = (string) ($context['detected_at'] ?? '');
        $effectiveFrom = $this->parseEffectiveFrom($text) ?? ($detectedAt !== '' ? substr($detectedAt, 0, 10) : now()->toDateString());

        // Threshold: often "P1,500 and below" or "1,500 and below".
        $threshold = null;
        if (preg_match('/\b(\d{1,3}(?:,\d{3})*(?:\.\d+)?)\b\s*(?:and\s+below|below)\b/i', $text, $m)) {
            $threshold = (float) str_replace(',', '', $m[1]);
        }
        if ($threshold === null && preg_match('/\bthreshold\b.{0,50}?\b(\d{1,3}(?:,\d{3})*(?:\.\d+)?)\b/i', $text, $m)) {
            $threshold = (float) str_replace(',', '', $m[1]);
        }

        // Employee rates: try to locate 1% and 2% commonly used.
        $belowRate = $this->parseRateNearPhrase($text, ['employee', '1%']) ?? $this->parseRateNearKeyword($text, ['employee']);
        $aboveRate = null;

        // If we have a threshold, attempt to find an "over" clause.
        if ($threshold !== null) {
            $overPattern = '/over\s+'.preg_quote((string) (int) $threshold, '/').'/i';
            if (preg_match($overPattern.'.{0,120}?(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
                $aboveRate = ((float) $m[1]) / 100.0;
            }
        }

        // Employer rate: typically 2%.
        $employerRate = $this->parseRateNearKeyword($text, ['employer']);

        // Cap: "maximum monthly compensation" or "maximum contribution".
        $cap = null;
        if (preg_match('/\b(max(?:imum)?\s+(?:monthly\s+)?contribution|cap)\b.{0,120}?(\d{1,3}(?:,\d{3})*(?:\.\d+)?)/i', $text, $m)) {
            $cap = (float) str_replace(',', '', $m[2]);
        }

        if ($belowRate === null && $aboveRate === null && $employerRate === null) {
            throw new InvalidArgumentException('Unable to parse Pag-IBIG rates from HTML/text. Consider using a JSON/CSV source or a PDF circular URL.');
        }

        return [
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'salary_threshold' => $threshold,
            'employee_rate_below_threshold' => $belowRate,
            'employee_rate_above_threshold' => $aboveRate ?? $belowRate,
            'employer_rate' => $employerRate,
            'monthly_cap' => $cap,
            'notes' => 'Parsed from '.(($context['format'] ?? null) === 'pdf' ? 'PDF' : 'HTML')." source (".((string) ($context['final_url'] ?? $context['source_url'] ?? '')).")",
        ];
    }

    private function looksLikeJson(string $content): bool
    {
        $c = ltrim($content);
        return str_starts_with($c, '{') || str_starts_with($c, '[');
    }

    private function looksLikeCsv(string $content): bool
    {
        return str_contains($content, ',') && str_contains($content, "\n");
    }

    private function normalizeText(string $text): string
    {
        $t = str_replace(["\r\n", "\r"], "\n", $text);
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;
        return trim($t);
    }

    private function parseEffectiveFrom(string $text): ?string
    {
        $months = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?(\d{1,2})\s+'.$months.'\s+(\d{4})/i', $text, $m)) {
            return date('Y-m-d', strtotime($m[1].' '.$m[2].' '.$m[3]));
        }

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?'.$months.'\s+(\d{4})/i', $text, $m)) {
            return date('Y-m-d', strtotime('1 '.$m[1].' '.$m[2]));
        }

        return null;
    }

    private function parseRateNearPhrase(string $text, array $phrases): ?float
    {
        // If any phrase exists, grab the first nearby percent.
        foreach ($phrases as $p) {
            if (stripos($text, $p) === false) {
                continue;
            }
            $pattern = '/'.preg_quote($p, '/').'.{0,80}?(\d{1,2}(?:\.\d+)?)\s*%/i';
            if (preg_match($pattern, $text, $m)) {
                return ((float) $m[1]) / 100.0;
            }
        }

        return null;
    }

    private function parseRateNearKeyword(string $text, array $keywords): ?float
    {
        $kw = implode('|', array_map('preg_quote', $keywords));
        if (preg_match('/(?:'.$kw.').{0,80}?(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
            return ((float) $m[1]) / 100.0;
        }
        if (preg_match('/(?:'.$kw.').{0,80}?\b(0\.\d{2,6})\b/i', $text, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    /** @return array<string,mixed> */
    private function parseSingleRowCsv(string $content, array $requiredColumns): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));
        if (count($lines) < 2) {
            throw new InvalidArgumentException('CSV must include a header and at least one row.');
        }

        $header = array_map('trim', str_getcsv($lines[0]));
        $index = array_flip($header);

        foreach ($requiredColumns as $col) {
            if (!array_key_exists($col, $index)) {
                throw new InvalidArgumentException("CSV missing required column '{$col}'.");
            }
        }

        $row = str_getcsv($lines[1]);
        $get = fn (string $col) => $row[$index[$col]] ?? null;

        $toNull = fn ($v) => (($t = trim((string) ($v ?? ''))) === '' ? null : $t);
        $toNum = function ($v) {
            $t = trim((string) ($v ?? ''));
            if ($t === '') return null;
            $t = preg_replace('/[\s,\x{20B1}]/u', '', $t) ?? $t;
            return (float) $t;
        };

        return [
            'effective_from' => $toNull($get('effective_from')),
            'effective_to' => $toNull($get('effective_to')),
            'employee_rate_below_threshold' => $toNum($get('employee_rate_below_threshold')),
            'employee_rate_above_threshold' => $toNum($get('employee_rate_above_threshold')),
            'employer_rate' => $toNum($get('employer_rate')),
            'salary_threshold' => $toNum($get('salary_threshold')),
            'monthly_cap' => $toNum($get('monthly_cap')),
            'notes' => $toNull($get('notes')),
        ];
    }
}
