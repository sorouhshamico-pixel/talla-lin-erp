<?php

namespace App\Services;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ReportSavedViewShareActivityRetentionAdminService
{
    public const LOCK_NAME = 'saved-view-share-activity-retention-prune';

    public function status(): array
    {
        $query = DB::table('report_saved_view_share_activities');
        $days = config('reports.saved_view_share_activity_retention.days');

        return [
            'retention_enabled' => (bool) config('reports.saved_view_share_activity_retention.enabled', false),
            'retention_days' => is_numeric($days) ? (int) $days : null,
            'chunk_size' => (int) config('reports.saved_view_share_activity_retention.chunk_size', 500),
            'schedule' => (string) config('reports.saved_view_share_activity_retention.schedule', 'daily'),
            'candidate_count' => is_numeric($days)
                ? (clone $query)->where('created_at', '<', now()->subDays((int) $days))->count()
                : null,
            'oldest_activity_at' => (clone $query)->min('created_at'),
            'newest_activity_at' => (clone $query)->max('created_at'),
            'last_manual_preview' => Cache::get('saved-view-share-activity-retention:last-preview'),
            'last_manual_execution' => Cache::get('saved-view-share-activity-retention:last-execution'),
        ];
    }

    public function preview(
        User $actor,
        int $days,
        ReportSavedViewShareActivityRetentionService $retention,
        ReportSavedViewShareActivityRetentionExecutionHistoryService $history
    ): array {
        $startedAt = now();

        try {
            $result = $retention->preview($days);
            $history->success(
                ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                $actor->id,
                $days,
                null,
                $result,
                $startedAt
            );
        } catch (Throwable $exception) {
            $history->failure(
                ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                $actor->id,
                $days,
                null,
                $exception,
                $startedAt
            );
            throw $exception;
        }

        $record = $result + [
            'actor_user_id' => $actor->id,
            'requested_days' => $days,
            'requested_chunk_size' => null,
        ];

        Cache::put('saved-view-share-activity-retention:last-preview', $record, now()->addDays(30));
        Log::info('Saved view sharing activity retention manual preview.', $record);

        return $record;
    }

    public function execute(
        User $actor,
        int $days,
        int $chunkSize,
        ReportSavedViewShareActivityRetentionService $retention,
        ReportSavedViewShareActivityRetentionExecutionHistoryService $history
    ): array {
        $lock = Cache::lock(self::LOCK_NAME, 3600);

        if (! $lock->get()) {
            $history->conflict($actor->id, $days, $chunkSize);
            throw new RuntimeException('Retention pruning is already running.', 409);
        }

        $startedAt = now();

        try {
            $result = $retention->prune($days, $chunkSize);
            $history->success(
                ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                $actor->id,
                $days,
                $chunkSize,
                $result,
                $startedAt
            );

            $record = $result + [
                'actor_user_id' => $actor->id,
                'requested_days' => $days,
                'requested_chunk_size' => $chunkSize,
            ];

            Cache::put('saved-view-share-activity-retention:last-execution', $record, now()->addDays(30));
            Log::info('Saved view sharing activity retention manual execution.', $record);

            return $record;
        } catch (Throwable $exception) {
            $history->failure(
                ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                $actor->id,
                $days,
                $chunkSize,
                $exception,
                $startedAt
            );
            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
