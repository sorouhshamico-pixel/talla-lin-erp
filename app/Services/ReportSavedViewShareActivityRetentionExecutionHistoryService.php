<?php

namespace App\Services;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ReportSavedViewShareActivityRetentionExecutionHistoryService
{
    public const SUMMARY_CACHE_EVENT_GENERATION_ROTATED =
        'saved_view_retention.summary_cache.generation_rotated';
    public const SUMMARY_CACHE_EVENT_GENERATION_ROTATION_FAILED =
        'saved_view_retention.summary_cache.generation_rotation_failed';

    public function success(
        string $type,
        ?int $actorUserId,
        int $days,
        ?int $chunkSize,
        array $result,
        mixed $startedAt
    ): void {
        $this->write([
            'type' => $type,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
            'actor_user_id' => $actorUserId,
            'requested_days' => $days,
            'requested_chunk_size' => $chunkSize,
            'candidate_count' => $result['candidate_count'] ?? null,
            'deleted_count' => $result['deleted_count'] ?? null,
            'cutoff_at' => $result['cutoff'] ?? null,
            'duration_ms' => $result['duration_ms'] ?? null,
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    public function failure(
        string $type,
        ?int $actorUserId,
        int $days,
        ?int $chunkSize,
        Throwable $exception,
        mixed $startedAt
    ): void {
        $this->write([
            'type' => $type,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
            'actor_user_id' => $actorUserId,
            'requested_days' => $days,
            'requested_chunk_size' => $chunkSize,
            'failure_class' => $exception::class,
            'failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);
    }

    public function conflict(?int $actorUserId, int $days, int $chunkSize): void
    {
        $this->write([
            'type' => ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
            'status' => ReportSavedViewShareActivityRetentionExecution::STATUS_CONFLICTED,
            'actor_user_id' => $actorUserId,
            'requested_days' => $days,
            'requested_chunk_size' => $chunkSize,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    private function write(array $attributes): void
    {
        try {
            ReportSavedViewShareActivityRetentionExecution::query()
                ->create($attributes);
        } catch (Throwable $exception) {
            Log::error('Retention execution history write failed.', [
                'history_error' => $exception->getMessage(),
                'operation_type' => $attributes['type'] ?? null,
                'operation_status' => $attributes['status'] ?? null,
            ]);

            return;
        }

        $this->invalidateSummaryCache();
    }

    private function invalidateSummaryCache(): void
    {
        try {
            Cache::put(
                ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_KEY,
                (string) Str::uuid(),
                now()->addSeconds(
                    ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_TTL_SECONDS
                )
            );

            $this->observe(
                'debug',
                self::SUMMARY_CACHE_EVENT_GENERATION_ROTATED,
                [
                    'cache_key_prefix' =>
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_KEY_PREFIX,
                    'ttl_seconds' =>
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_GENERATION_TTL_SECONDS,
                ]
            );
        } catch (Throwable $exception) {
            $this->observe(
                'warning',
                self::SUMMARY_CACHE_EVENT_GENERATION_ROTATION_FAILED,
                [
                    'cache_key_prefix' =>
                        ReportSavedViewShareActivityRetentionExecutionHistoryExportService::SUMMARY_CACHE_KEY_PREFIX,
                    'fallback_reason_class' =>
                        $exception::class,
                ]
            );
        }
    }

    private function observe(
        string $level,
        string $event,
        array $context
    ): void {
        try {
            Log::log(
                $level,
                $event,
                array_merge(['event' => $event], $context)
            );
        } catch (Throwable) {
            // Observability must never change history-write behavior.
        }
    }
}
