<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GovernmentSourceContentExtractor
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';

    /**
     * Fetches the configured source and returns content suitable for hashing and parsing.
     *
     * Supports:
     * - HTML: extracts readable text; optionally follows a linked PDF circular.
     * - PDF: extracts text via pdftotext (Poppler).
     * - JSON/CSV/TXT: returns raw.
     *
     * @return array{
     *   final_url: string,
     *   content_type: string,
     *   raw: string,
     *   text: string,
     *   format: 'pdf'|'html'|'json'|'csv'|'text',
    *   pdf_url: string|null,
    *   blocked?: bool
     * }
     */
    public function fetch(string $url): array
    {
        $response = $this->httpGet($url);
        $contentType = trim((string) $response->header('Content-Type'));
        $raw = (string) $response->body();

        $format = $this->detectFormat($url, $contentType, $raw);

        if ($format === 'pdf') {
            $text = $this->extractPdfText($raw);

            return [
                'final_url' => $url,
                'content_type' => $contentType,
                'raw' => $raw,
                'text' => $text,
                'format' => 'pdf',
                'pdf_url' => $url,
            ];
        }

        if ($format === 'html') {
            $blocked = $this->looksLikeBotProtectionPage($raw);

            $preferPdf = (bool) config('government_monitor.prefer_pdf_links', true);
            if ($preferPdf) {
                $pdfUrl = $this->extractBestPdfLink($raw, $url);
                if ($pdfUrl) {
                    try {
                        $pdfResponse = $this->httpGet($pdfUrl);
                        $pdfCt = trim((string) $pdfResponse->header('Content-Type'));
                        $pdfRaw = (string) $pdfResponse->body();

                        if ($this->detectFormat($pdfUrl, $pdfCt, $pdfRaw) === 'pdf') {
                            $text = $this->extractPdfText($pdfRaw);

                            return [
                                'final_url' => $pdfUrl,
                                'content_type' => $pdfCt !== '' ? $pdfCt : 'application/pdf',
                                'raw' => $pdfRaw,
                                'text' => $text,
                                'format' => 'pdf',
                                'pdf_url' => $pdfUrl,
                            ];
                        }
                    } catch (Exception) {
                        // If PDF following/extraction fails (e.g., pdftotext missing), fall back to parsing HTML.
                    }
                }
            }

            $text = $this->extractHtmlText($raw);

            return [
                'final_url' => $url,
                'content_type' => $contentType,
                'raw' => $raw,
                'text' => $text,
                'format' => 'html',
                'pdf_url' => null,
                'blocked' => $blocked,
            ];
        }

        // json/csv/text
        return [
            'final_url' => $url,
            'content_type' => $contentType,
            'raw' => $raw,
            'text' => $raw,
            'format' => $format,
            'pdf_url' => null,
        ];
    }

    private function httpGet(string $url): Response
    {
        $response = Http::timeout(45)
            ->retry(2, 800)
            ->withHeaders([
                'User-Agent' => self::DEFAULT_USER_AGENT,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ])
            ->withOptions([
                'allow_redirects' => ['max' => 5],
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new Exception("HTTP {$response->status()} fetching {$url}");
        }

        return $response;
    }

    private function looksLikeBotProtectionPage(string $raw): bool
    {
        $h = strtolower(substr($raw, 0, 20000));

        if (str_contains($h, 'checking your browser before accessing')) {
            return true;
        }
        if (str_contains($h, 'cf-browser-verification') || str_contains($h, 'cf-challenge') || str_contains($h, 'cloudflare')) {
            return true;
        }
        if (str_contains($h, 'cdn-cgi') && (str_contains($h, 'challenge') || str_contains($h, 'browser verification'))) {
            return true;
        }

        return false;
    }

    private function detectFormat(string $url, string $contentType, string $raw): string
    {
        $ct = strtolower($contentType);
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?: '');

        if (str_contains($ct, 'pdf') || Str::endsWith($path, '.pdf') || $this->looksLikePdf($raw)) {
            return 'pdf';
        }

        if (str_contains($ct, 'json') || Str::endsWith($path, '.json')) {
            return 'json';
        }

        if (str_contains($ct, 'csv') || Str::endsWith($path, '.csv')) {
            return 'csv';
        }

        if (str_contains($ct, 'html') || Str::endsWith($path, '.htm') || Str::endsWith($path, '.html') || $this->looksLikeHtml($raw)) {
            return 'html';
        }

        return 'text';
    }

    private function looksLikePdf(string $raw): bool
    {
        // PDFs usually start with %PDF-
        $prefix = substr($raw, 0, 8);
        return str_starts_with($prefix, '%PDF-');
    }

    private function looksLikeHtml(string $raw): bool
    {
        $c = ltrim($raw);
        return str_starts_with($c, '<!doctype') || str_starts_with($c, '<html') || str_contains(substr($c, 0, 2000), '<html') || str_contains(substr($c, 0, 2000), '<body');
    }

    private function extractBestPdfLink(string $html, string $baseUrl): ?string
    {
        // Find all href="...pdf" and pick the most likely circular/table document.
        if (! preg_match_all('~href\s*=\s*["\']([^"\']+\.pdf[^"\']*)["\']~i', $html, $matches)) {
            return null;
        }

        $hrefs = $matches[1] ?? [];
        if (! is_array($hrefs) || count($hrefs) === 0) {
            return null;
        }

        $bestUrl = null;
        $bestScore = 0;

        foreach ($hrefs as $href) {
            $href = html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5);
            $href = trim($href);
            if ($href === '') {
                continue;
            }

            try {
                $base = new Uri($baseUrl);
                $rel = new Uri($href);
                $resolved = UriResolver::resolve($base, $rel);

                $scheme = strtolower((string) $resolved->getScheme());
                if (! in_array($scheme, ['http', 'https'], true)) {
                    continue;
                }

                $candidate = (string) $resolved;
                $score = $this->scorePdfCandidate($candidate);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestUrl = $candidate;
                }
            } catch (Exception) {
                continue;
            }
        }

        return $bestScore > 0 ? $bestUrl : null;
    }

    private function scorePdfCandidate(string $url): int
    {
        $u = strtolower($url);

        $score = 0;
        foreach (['cir', 'circular', 'contribution', 'contri', 'premium', 'table', 'schedule', 'employer', 'employee'] as $kw) {
            if (str_contains($u, $kw)) {
                $score += 3;
            }
        }

        foreach (['npc_seal', 'privacy', 'terms', 'charter', 'seal', 'foi', 'policy', 'procurement', 'bidding'] as $bad) {
            if (str_contains($u, $bad)) {
                $score -= 5;
            }
        }

        return $score;
    }

    private function extractHtmlText(string $html): string
    {
        // Remove scripts/styles first.
        $s = preg_replace('~<script\b[^>]*>.*?</script>~is', ' ', $html) ?? $html;
        $s = preg_replace('~<style\b[^>]*>.*?</style>~is', ' ', $s) ?? $s;

        // Preserve some structure.
        $s = preg_replace('~<(br|/p|/div|/li|/tr|/h\d)>~i', "\n", $s) ?? $s;

        $text = strip_tags($s);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // Normalize whitespace.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = preg_replace("/[\t\x0B\f]+/", " ", $text) ?? $text;
        $text = preg_replace("/ {2,}/", " ", $text) ?? $text;

        return trim($text);
    }

    private function extractPdfText(string $pdfRaw): string
    {
        $pdftotext = trim((string) config('government_monitor.pdftotext_path', ''));
        $timeout = (int) config('government_monitor.pdftotext_timeout', 60);

        $bin = $pdftotext !== '' ? $pdftotext : 'pdftotext';

        $pdfPath = tempnam(sys_get_temp_dir(), 'crewly_gov_pdf_');
        $txtPath = tempnam(sys_get_temp_dir(), 'crewly_gov_txt_');
        if ($pdfPath === false || $txtPath === false) {
            throw new Exception('Failed to create temporary files for PDF extraction.');
        }

        // Ensure correct extensions (some tools behave better).
        $pdfFile = $pdfPath.'.pdf';
        $txtFile = $txtPath.'.txt';

        @rename($pdfPath, $pdfFile);
        @rename($txtPath, $txtFile);

        file_put_contents($pdfFile, $pdfRaw);

        $process = new Process([$bin, '-layout', '-nopgbrk', $pdfFile, $txtFile]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (Exception $e) {
            @unlink($pdfFile);
            @unlink($txtFile);
            throw new Exception('pdftotext execution failed: '.$e->getMessage());
        }

        $stdout = (string) $process->getOutput();
        $stderr = (string) $process->getErrorOutput();

        if (! $process->isSuccessful()) {
            @unlink($pdfFile);
            @unlink($txtFile);
            throw new Exception('pdftotext failed. '.trim($stderr !== '' ? $stderr : $stdout));
        }

        $text = is_file($txtFile) ? (string) file_get_contents($txtFile) : '';

        @unlink($pdfFile);
        @unlink($txtFile);

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
