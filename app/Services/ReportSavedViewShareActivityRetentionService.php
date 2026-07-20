<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class ReportSavedViewShareActivityRetentionService
{
    public const MINIMUM_DAYS = 30;
    public const MAXIMUM_DAYS = 3650;
    public const DEFAULT_CHUNK_SIZE = 500;

    public function preview(int $days): array
    {
        $cutoff = $this->cutoff($days);
        $startedAt = microtime(true);
        $count = $this->eligibleQuery($cutoff)->count();

        return [
            'dry_run' => true,
            'candidate_count' => $count,
            'deleted_count' => 0,
            'cutoff' => $cutoff->toIso8601String(),
            'duration_ms' => $this->duration($startedAt),
        ];
    }

    public function prune(
        int $days,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE
    ): array {
        $cutoff = $this->cutoff($days);
        $chunkSize = $this->validateChunkSize($chunkSize);
        $startedAt = microtime(true);
        $deleted = 0;

        while (true) {
            $ids = $this->eligibleQuery($cutoff)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            try {
                $deleted += DB::transaction(
                    fn (): int => DB::table(
                        'report_saved_view_share_activities'
                    )
                        ->whereIn('id', $ids->all())
                        ->delete()
                );
            } catch (Throwable $exception) {
                Log::error(
                    'Saved view sharing activity pruning failed.',
                    [
                        'cutoff' => $cutoff->toIso8601String(),
                        'deleted_count' => $deleted,
                        'error' => $exception->getMessage(),
                    ]
                );

                throw $exception;
            }
        }

        $result = [
            'dry_run' => false,
            'candidate_count' => $deleted,
            'deleted_count' => $deleted,
            'cutoff' => $cutoff->toIso8601String(),
            'duration_ms' => $this->duration($startedAt),
        ];

        Log::info(
            'Saved view sharing activity pruning completed.',
            $result
        );

        return $result;
    }

    private function eligibleQuery(
        CarbonImmutable $cutoff
    ): Builder {
        return DB::table(
            'report_saved_view_share_activities'
        )->where(
            'created_at',
            '<',
            $cutoff
        );
    }

    private function cutoff(
        int $days
    ): CarbonImmutable {
        if (
            $days < self::MINIMUM_DAYS
            || $days > self::MAXIMUM_DAYS
        ) {
            throw new InvalidArgumentException(
                'Retention days must be between 30 and 3650.'
            );
        }

        return CarbonImmutable::now()->subDays($days);
    }

    private function validateChunkSize(
        int $chunkSize
    ): int {
        if ($chunkSize < 1 || $chunkSize > 10000) {
            throw new InvalidArgumentException(
                'Chunk size must be between 1 and 10000.'
            );
        }

        return $chunkSize;
    }

    private function duration(
        float $startedAt
    ): int {
        return (int) round(
            (microtime(true) - $startedAt) * 1000
        );
    }
}
