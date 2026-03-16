<?php

namespace App\Services\GovernmentParsers;

use InvalidArgumentException;

class PhilhealthParser implements \App\Services\GovernmentParsers\GovernmentParser
{
    public function parse(string $rawContent, array $context): array
    {
        $content = trim($rawContent);
        if ($content === '') {
            throw new InvalidArgumentException('Empty content.');
        }

        // Preferred format: JSON object matching Crewly fields.
        if ($this->looksLikeJson($content)) {
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                throw new InvalidArgumentException('Invalid JSON for PhilHealth payload.');
            }

            // Accept either object or [object]
            if (array_is_list($decoded)) {
                $decoded = $decoded[0] ?? null;
                if (!is_array($decoded)) {
                    throw new InvalidArgumentException('PhilHealth JSON must be an object or an array with one object.');
                }
            }

            return $decoded;
        }

        // Minimal CSV support: header row, single data row.
        if ($this->looksLikeCsv($content)) {
            return $this->parseSingleRowCsv($content, [
                'effective_from',
                'effective_to',
                'premium_rate',
                'salary_floor',
                'salary_ceiling',
                'employee_share_percent',
                'employer_share_percent',
                'notes',
            ]);
        }

        // HTML/text heuristic parsing (official pages often publish rates in prose).
        return $this->parseFromText($content, $context);
    }

    /** @return array<string,mixed> */
    private function parseFromText(string $content, array $context): array
    {
        $text = $this->normalizeText($content);

        // Effective date: best-effort; fall back to detected_at date.
        $detectedAt = (string) ($context['detected_at'] ?? '');
        $effectiveFrom = $this->parseEffectiveFrom($text) ?? ($detectedAt !== '' ? substr($detectedAt, 0, 10) : now()->toDateString());

        // Premium rate: prefer explicit percentage near "premium" keywords.
        $premiumRate = $this->parseRateNearKeywords($text, ['premium', 'rate', 'contribution']);

        // Salary floor/ceiling: capture largest 2 monetary amounts near "floor"/"ceiling".
        $floor = $this->parseMoneyNearKeyword($text, ['floor', 'minimum']);
        $ceiling = $this->parseMoneyNearKeyword($text, ['ceiling', 'maximum']);

        // Split: many years are 50/50. Parse if present; otherwise default to 0.5/0.5.
        $employeeShare = $this->parseSharePercent($text, 'employee') ?? 0.5;
        $employerShare = $this->parseSharePercent($text, 'employer') ?? 0.5;

        // If premium rate is still unknown, fail (we don't want to create unusable drafts).
        if ($premiumRate === null) {
            throw new InvalidArgumentException('Unable to parse PhilHealth premium rate from HTML/text. Consider using a JSON/CSV source or a PDF circular URL.');
        }

        return [
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'premium_rate' => $premiumRate,
            'salary_floor' => $floor,
            'salary_ceiling' => $ceiling,
            'employee_share_percent' => $employeeShare,
            'employer_share_percent' => $employerShare,
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
        // Common patterns: "Effective January 2024", "effective on 01 January 2024"
        $months = '(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?(\d{1,2})\s+'.$months.'\s+(\d{4})/i', $text, $m)) {
            $day = (int) $m[1];
            $month = $m[2];
            $year = (int) $m[3];
            return date('Y-m-d', strtotime("{$day} {$month} {$year}"));
        }

        if (preg_match('/effective\s+(?:on\s+|starting\s+|from\s+)?'.$months.'\s+(\d{4})/i', $text, $m)) {
            $month = $m[1];
            $year = (int) $m[2];
            return date('Y-m-d', strtotime("1 {$month} {$year}"));
        }

        return null;
    }

    private function parseRateNearKeywords(string $text, array $keywords): ?float
    {
        $kw = implode('|', array_map('preg_quote', $keywords));
        // Capture something like 5% or 0.05 within ~80 chars of keywords.
        if (preg_match('/(?:'.$kw.').{0,80}?(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
            return ((float) $m[1]) / 100.0;
        }
        if (preg_match('/(?:'.$kw.').{0,80}?\b(0\.\d{2,6})\b/i', $text, $m)) {
            return (float) $m[1];
        }
        // Fallback: take the first percentage that looks like a plausible premium rate.
        if (preg_match('/\b(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
            $p = (float) $m[1];
            if ($p > 0 && $p < 20) {
                return $p / 100.0;
            }
        }
        return null;
    }

    private function parseMoneyNearKeyword(string $text, array $keywords): ?float
    {
        $kw = implode('|', array_map('preg_quote', $keywords));
        if (preg_match('/(?:'.$kw.').{0,120}?(\d{1,3}(?:,\d{3})*(?:\.\d+)?)/i', $text, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }
        return null;
    }

    private function parseSharePercent(string $text, string $label): ?float
    {
        // Employee share: 50% / 0.5 etc.
        if (preg_match('/\b'.preg_quote($label, '/').'\b.{0,50}?(\d{1,2}(?:\.\d+)?)\s*%/i', $text, $m)) {
            return ((float) $m[1]) / 100.0;
        }
        if (preg_match('/\b'.preg_quote($label, '/').'\b.{0,50}?\b(0\.\d{1,4})\b/i', $text, $m)) {
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
            'premium_rate' => $toNum($get('premium_rate')),
            'salary_floor' => $toNum($get('salary_floor')),
            'salary_ceiling' => $toNum($get('salary_ceiling')),
            'employee_share_percent' => $toNum($get('employee_share_percent')),
            'employer_share_percent' => $toNum($get('employer_share_percent')),
            'notes' => $toNull($get('notes')),
        ];
    }
}
