<?php

namespace App\Services;

use App\Models\GovernmentSourceMonitor;
use App\Models\GovernmentUpdateDraft;
use App\Models\User;
use App\Notifications\GovernmentUpdateDraftDetected;
use App\Services\GovernmentParsers\PagibigParser;
use App\Services\GovernmentParsers\PhilhealthParser;
use App\Services\GovernmentParsers\SssParser;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GovernmentContributionMonitorService
{
    public function __construct(
        private readonly GovernmentSourceContentExtractor $extractor = new GovernmentSourceContentExtractor(),
    ) {
    }

    public function checkAll(): array
    {
        return [
            GovernmentSourceMonitor::TYPE_SSS => $this->checkSSS(),
            GovernmentSourceMonitor::TYPE_PHILHEALTH => $this->checkPhilHealth(),
            GovernmentSourceMonitor::TYPE_PAGIBIG => $this->checkPagibig(),
        ];
    }

    public function checkSSS(): array
    {
        return $this->checkSource(GovernmentSourceMonitor::TYPE_SSS, new SssParser());
    }

    public function checkPhilHealth(): array
    {
        return $this->checkSource(GovernmentSourceMonitor::TYPE_PHILHEALTH, new PhilhealthParser());
    }

    public function checkPagibig(): array
    {
        return $this->checkSource(GovernmentSourceMonitor::TYPE_PAGIBIG, new PagibigParser());
    }

    private function checkSource(string $sourceType, object $parser): array
    {
        $url = trim((string) config("government_monitor.sources.{$sourceType}.url", ''));

        $monitor = GovernmentSourceMonitor::query()->firstOrCreate(
            ['source_type' => $sourceType],
            [
                'source_url' => $url,
                'last_status' => null,
            ]
        );

        if ($url !== '' && $monitor->source_url !== $url) {
            $monitor->forceFill(['source_url' => $url])->save();
        }

        $urlToFetch = trim((string) ($monitor->source_url ?? ''));
        if ($urlToFetch === '') {
            $monitor->forceFill([
                'last_checked_at' => now(),
                'last_status' => GovernmentSourceMonitor::STATUS_FAILED,
                'last_error' => 'Missing source URL. Set GOV_MONITOR_*_URL in .env (see config/government_monitor.php).',
            ])->save();

            return ['status' => 'failed', 'error' => 'missing_url'];
        }

        try {
            $fetched = $this->extractor->fetch($urlToFetch);

            $raw = (string) ($fetched['raw'] ?? '');
            $text = (string) ($fetched['text'] ?? '');
            $contentType = (string) ($fetched['content_type'] ?? '');
            $format = (string) ($fetched['format'] ?? 'text');
            $finalUrl = (string) ($fetched['final_url'] ?? $urlToFetch);

            $normalized = $this->normalize($text !== '' ? $text : $raw);
            $hash = hash('sha256', $normalized);

            $snapshotPath = $this->storeSnapshot($sourceType, $hash, $raw, $contentType);

            $blocked = (bool) ($fetched['blocked'] ?? false);
            if ($blocked) {
                $monitor->forceFill([
                    'last_checked_at' => now(),
                    'raw_snapshot_path' => $snapshotPath,
                    'last_status' => GovernmentSourceMonitor::STATUS_FAILED,
                    'last_error' => 'Source appears protected by a browser/JS challenge (e.g., Cloudflare). Use an official PDF/JSON/CSV URL instead.',
                ])->save();

                return [
                    'status' => 'failed',
                    'error' => 'blocked_by_challenge',
                ];
            }

            $monitor->forceFill([
                'last_checked_at' => now(),
                'last_hash' => $hash,
                'raw_snapshot_path' => $snapshotPath,
                'last_error' => null,
            ]);

            $previousHash = trim((string) ($monitor->getOriginal('last_hash') ?? ''));

            if ($previousHash === '' || $previousHash === $hash) {
                $monitor->forceFill(['last_status' => GovernmentSourceMonitor::STATUS_OK])->save();

                return ['status' => 'ok', 'changed' => false, 'hash' => $hash];
            }

            // Content changed: parse and create a draft.
            $detectedAt = now();

            $parseError = null;
            $payload = [];
            try {
                $payload = $parser->parse($format === 'html' || $format === 'pdf' ? $text : $raw, [
                    'source_type' => $sourceType,
                    'source_url' => $urlToFetch,
                    'final_url' => $finalUrl,
                    'content_type' => $contentType !== '' ? $contentType : null,
                    'format' => $format,
                    'pdf_url' => $fetched['pdf_url'] ?? null,
                    'detected_at' => $detectedAt->toDateTimeString(),
                ]);

                if (!is_array($payload) || count($payload) === 0) {
                    $parseError = 'Parser returned an empty payload.';
                    $payload = [];
                }
            } catch (Exception $e) {
                $parseError = $e->getMessage();
                $payload = [];
            }

            $draft = DB::transaction(function () use ($sourceType, $detectedAt, $urlToFetch, $hash, $payload, $parseError) {
                $existingDraft = GovernmentUpdateDraft::query()
                    ->where('source_type', $sourceType)
                    ->where('content_hash', $hash)
                    ->latest('detected_at')
                    ->first();

                if ($existingDraft) {
                    return $existingDraft;
                }

                return GovernmentUpdateDraft::query()->create([
                    'source_type' => $sourceType,
                    'detected_at' => $detectedAt,
                    'source_url' => $urlToFetch,
                    'content_hash' => $hash,
                    'status' => GovernmentUpdateDraft::STATUS_DRAFT,
                    'parsed_payload' => $payload,
                    'parse_error' => $parseError,
                ]);
            });

            $monitor->forceFill(['last_status' => GovernmentSourceMonitor::STATUS_PENDING_REVIEW])->save();

            $this->notifyAdminsOfDraft($draft);

            return [
                'status' => 'changed',
                'changed' => true,
                'hash' => $hash,
                'draft_id' => (int) $draft->id,
            ];
        } catch (Exception $e) {
            Log::warning('Government contribution monitor failed', [
                'source_type' => $sourceType,
                'source_url' => $urlToFetch,
                'error' => $e->getMessage(),
            ]);

            $monitor->forceFill([
                'last_checked_at' => now(),
                'last_status' => GovernmentSourceMonitor::STATUS_FAILED,
                'last_error' => $e->getMessage(),
            ])->save();

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function normalize(string $raw): string
    {
        $s = str_replace(["\r\n", "\r"], "\n", $raw);
        $s = preg_replace("/\n{3,}/", "\n\n", $s) ?? $s;
        return trim($s);
    }

    private function storeSnapshot(string $sourceType, string $hash, string $raw, string $contentType): string
    {
        $disk = config('government_monitor.snapshot_disk');
        $baseDir = trim((string) config('government_monitor.snapshot_dir', 'government-updates'));

        $safeType = Str::slug($sourceType);
        $dir = trim($baseDir, '/')."/{$safeType}";

        $ext = 'txt';
        $ct = strtolower($contentType);
        if (str_contains($ct, 'pdf')) {
            $ext = 'pdf';
        } elseif (str_contains($ct, 'json')) {
            $ext = 'json';
        } elseif (str_contains($ct, 'csv')) {
            $ext = 'csv';
        } elseif (str_contains($ct, 'html')) {
            $ext = 'html';
        }

        $filename = now()->format('Ymd_His')."_{$hash}.{$ext}";
        $path = $dir.'/'.$filename;

        $storage = $disk ? Storage::disk($disk) : Storage::disk();
        $storage->put($path, $raw);

        return $path;
    }

    private function notifyAdminsOfDraft(GovernmentUpdateDraft $draft): void
    {
        $admins = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->whereNull('deleted_at')
            ->get(['id', 'email', 'role']);

        foreach ($admins as $admin) {
            try {
                $admin->notify(new GovernmentUpdateDraftDetected([
                    'source_type' => (string) $draft->source_type,
                    'draft_id' => (int) $draft->id,
                    'message' => 'New '.strtoupper((string) $draft->source_type).' contribution update draft detected and ready for review.',
                    'url' => route('admin.government_updates.drafts.show', $draft->id),
                ]));
            } catch (Exception $e) {
                Log::warning('Failed to notify admin of government update draft', [
                    'draft_id' => (int) $draft->id,
                    'user_id' => (int) $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
