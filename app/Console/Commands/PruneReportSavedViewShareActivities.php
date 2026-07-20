<?php

namespace App\Console\Commands;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use App\Services\ReportSavedViewShareActivityRetentionService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class PruneReportSavedViewShareActivities extends Command
{
    protected $signature = 'reports:prune-saved-view-share-activities
        {--days= : Retention period in days}
        {--dry-run : Report candidates without deleting}
        {--chunk= : Deletion chunk size}';

    protected $description = 'Prune saved view sharing activity rows according to retention policy.';

    public function handle(
        ReportSavedViewShareActivityRetentionService $service,
        ReportSavedViewShareActivityRetentionExecutionHistoryService $history
    ): int {
        $days = $this->resolveDays();
        $chunkSize = $this->resolveChunkSize();

        if ($days === null) {
            $this->error('Retention days are not configured. Use --days or configure reports retention days.');
            return self::FAILURE;
        }

        $startedAt = now();

        try {
            $result = $this->option('dry-run')
                ? $service->preview($days)
                : $service->prune($days, $chunkSize);

            $history->success(
                ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
                null,
                $days,
                $this->option('dry-run') ? null : $chunkSize,
                $result,
                $startedAt
            );
        } catch (InvalidArgumentException $exception) {
            $history->failure(
                ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
                null,
                $days,
                $chunkSize,
                $exception,
                $startedAt
            );
            $this->error($exception->getMessage());
            return self::INVALID;
        } catch (Throwable $exception) {
            $history->failure(
                ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
                null,
                $days,
                $chunkSize,
                $exception,
                $startedAt
            );
            $this->error('Pruning failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->table(['Metric', 'Value'], [
            ['Dry run', $result['dry_run'] ? 'yes' : 'no'],
            ['Candidates', $result['candidate_count']],
            ['Deleted', $result['deleted_count']],
            ['Cutoff', $result['cutoff']],
            ['Duration ms', $result['duration_ms']],
        ]);

        return self::SUCCESS;
    }

    private function resolveDays(): ?int
    {
        $option = $this->option('days');
        if (is_scalar($option) && trim((string) $option) !== '' && is_numeric($option)) {
            return (int) $option;
        }

        $configured = config('reports.saved_view_share_activity_retention.days');
        return ($configured === null || $configured === '') ? null : (int) $configured;
    }

    private function resolveChunkSize(): int
    {
        $option = $this->option('chunk');
        if (is_scalar($option) && trim((string) $option) !== '' && is_numeric($option)) {
            return (int) $option;
        }

        return (int) config(
            'reports.saved_view_share_activity_retention.chunk_size',
            ReportSavedViewShareActivityRetentionService::DEFAULT_CHUNK_SIZE
        );
    }
}
