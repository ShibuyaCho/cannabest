<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait TracksProgress
{
    /** Cache key with org context (primary) */
    public static function progressKey(string $jobId, ?int $orgId = null): string
    {
        return 'metrc_sync:job:' . ($orgId ?? 'global') . ':' . $jobId;
    }

    /** Org-agnostic alias so /metrc/sync-status/{jobId} can read without org */
    public static function progressKeyAlias(string $jobId): string
    {
        return 'metrc_sync:job:' . $jobId;
    }

    /** Write status + payload for UI polling */
    public function progressSet(
        string $jobId,
        string $status,
        string $message,
        int $pct = 0,
        array $payload = [],
        ?int $orgId = null
    ): void {
        $data = [
            'job_id'  => $jobId,
            'status'  => $status,
            'message' => $message,
            'pct'     => max(0, min(100, (int)$pct)),
            'payload' => $payload,
        ];
        $ttl = now()->addHours(2);

        // primary (org-scoped)
        Cache::put(self::progressKey($jobId, $orgId), $data, $ttl);
        // alias (org-agnostic)
        Cache::put(self::progressKeyAlias($jobId), $data, $ttl);
    }

    /** Read status (first alias, then org-scoped) */
    public function progressGet(?string $jobId, ?int $orgId = null): ?array
    {
        if (!$jobId) return null;
        $data = Cache::get(self::progressKeyAlias($jobId));
        if (!$data) $data = Cache::get(self::progressKey($jobId, $orgId));
        return is_array($data) ? $data : null;
    }

    /** Lightweight log helper used by the job */
    public function tracePush(string $jobId, string $event, array $context = [], ?int $orgId = null): void
    {
        try {
            Log::info('[METRC Sync] '.$event, array_merge(['job_id'=>$jobId,'org_id'=>$orgId], $context));
        } catch (\Throwable $e) {}
    }
}
