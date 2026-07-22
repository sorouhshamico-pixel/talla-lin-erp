<?php

namespace App\Services;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportSavedViewShareActivityRetentionExecutionHistoryExportService
{
    public const CSV_MAXIMUM_ROWS = 100000;
    public const JSON_MAXIMUM_ROWS = 10000;
    public const SUMMARY_MAXIMUM_QUERIES = 1;
    public const SUMMARY_TIMEOUT_SECONDS = 30;
    public const SUMMARY_CACHE_TTL_SECONDS = 30;
    public const SUMMARY_CACHE_KEY_PREFIX =
        'reports:saved-view-retention:execution-history-summary:v1';
    public const SUMMARY_CACHE_GENERATION_KEY =
        'reports:saved-view-retention:execution-history-summary:generation:v1';
    public const SUMMARY_CACHE_GENERATION_TTL_SECONDS = 86400;
    public const SUMMARY_CACHE_DEFAULT_GENERATION = '0';
    public const SUMMARY_CACHE_EVENT_HIT =
        'saved_view_retention.summary_cache.hit';
    public const SUMMARY_CACHE_EVENT_MISS =
        'saved_view_retention.summary_cache.miss';
    public const SUMMARY_CACHE_EVENT_FALLBACK =
        'saved_view_retention.summary_cache.fallback';
    public const SUMMARY_CACHE_EVENT_GENERATION_READ_FALLBACK =
        'saved_view_retention.summary_cache.generation_read_fallback';

    /**
     * @var list<string>
     */
    public const COLUMNS = [
        'id',
        'type',
        'status',
        'actor_user_id',
        'requested_days',
        'requested_chunk_size',
        'candidate_count',
        'deleted_count',
        'cutoff_at',
        'duration_ms',
        'failure_class',
        'failure_message',
        'started_at',
        'finished_at',
        'created_at',
    ];

    /**
     * @return array{
     *     type?: string,
     *     status?: string,
     *     actor_user_id?: int,
     *     started_from?: string,
     *     started_to?: string
     * }
     */
    public function validatedFilters(array $input): array
    {
        return Validator::make($input, [
            'type' => [
                'nullable',
                'string',
                Rule::in([
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_SCHEDULED_EXECUTION,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
                ]),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                    ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
                    ReportSavedViewShareActivityRetentionExecution::STATUS_CONFLICTED,
                ]),
            ],
            'actor_user_id' => ['nullable', 'integer', 'min:1'],
            'started_from' => ['nullable', 'date'],
            'started_to' => ['nullable', 'date', 'after_or_equal:started_from'],
        ])->validate();
    }

    public function query(array $filters): Builder
    {
        return $this->applyFilters(
            ReportSavedViewShareActivityRetentionExecution::query()
                ->select(self::COLUMNS)
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            $filters
        );
    }

    /**
     * @return array{
     *     total_count: int,
     *     succeeded_count: int,
     *     failed_count: int,
     *     conflicted_count: int,
     *     manual_preview_count: int,
     *     manual_execution_count: int,
     *     scheduled_execution_count: int,
     *     command_execution_count: int,
     *     candidate_count_sum: int,
     *     deleted_count_sum: int,
     *     average_duration_ms: int|null,
     *     oldest_started_at: string|null,
     *     newest_started_at: string|null
     * }
     */
    public function summary(array $filters): array
    {
        $normalizedFilters = $this->normalizedSummaryFilters($filters);
        $cacheMiss = false;

        try {
            $summary = Cache::remember(
                $this->summaryCacheKey(
                    $normalizedFilters,
                    $this->summaryCacheGeneration()
                ),
                now()->addSeconds(self::SUMMARY_CACHE_TTL_SECONDS),
                function () use (
                    $normalizedFilters,
                    &$cacheMiss
                ): array {
                    $cacheMiss = true;

                    $this->observe(
                        'debug',
                        self::SUMMARY_CACHE_EVENT_MISS,
                        [
                            'cache_key_prefix' =>
                                self::SUMMARY_CACHE_KEY_PREFIX,
                            'filter_count' =>
                                count($normalizedFilters),
                            'ttl_seconds' =>
                                self::SUMMARY_CACHE_TTL_SECONDS,
                        ]
                    );

                    return $this->liveSummary($normalizedFilters);
                }
            );

            if (! $cacheMiss) {
                $this->observe(
                    'debug',
                    self::SUMMARY_CACHE_EVENT_HIT,
                    [
                        'cache_key_prefix' =>
                            self::SUMMARY_CACHE_KEY_PREFIX,
                        'filter_count' => count($normalizedFilters),
                        'ttl_seconds' =>
                            self::SUMMARY_CACHE_TTL_SECONDS,
                    ]
                );
            }

            return $summary;
        } catch (Throwable $exception) {
            $this->observe(
                'warning',
                self::SUMMARY_CACHE_EVENT_FALLBACK,
                [
                    'cache_key_prefix' =>
                        self::SUMMARY_CACHE_KEY_PREFIX,
                    'filter_count' => count($normalizedFilters),
                    'fallback_reason_class' =>
                        $exception::class,
                ]
            );

            return $this->liveSummary($normalizedFilters);
        }
    }

    private function liveSummary(array $filters): array
    {
        $aggregate = $this->applyFilters(
            ReportSavedViewShareActivityRetentionExecution::query(),
            $filters
        )
            ->selectRaw(
                'COUNT(*) AS total_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS succeeded_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS failed_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS conflicted_count,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS manual_preview_count,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS manual_execution_count,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS scheduled_execution_count,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) AS command_execution_count,
                COALESCE(SUM(candidate_count), 0) AS candidate_count_sum,
                COALESCE(SUM(deleted_count), 0) AS deleted_count_sum,
                AVG(duration_ms) AS average_duration_ms,
                MIN(started_at) AS oldest_started_at,
                MAX(started_at) AS newest_started_at',
                [
                    ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED,
                    ReportSavedViewShareActivityRetentionExecution::STATUS_FAILED,
                    ReportSavedViewShareActivityRetentionExecution::STATUS_CONFLICTED,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_SCHEDULED_EXECUTION,
                    ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION,
                ]
            )
            ->toBase()
            ->first();

        $averageDuration = $aggregate?->average_duration_ms;

        return [
            'total_count' => (int) ($aggregate?->total_count ?? 0),
            'succeeded_count' => (int) ($aggregate?->succeeded_count ?? 0),
            'failed_count' => (int) ($aggregate?->failed_count ?? 0),
            'conflicted_count' => (int) ($aggregate?->conflicted_count ?? 0),
            'manual_preview_count' => (int) ($aggregate?->manual_preview_count ?? 0),
            'manual_execution_count' => (int) ($aggregate?->manual_execution_count ?? 0),
            'scheduled_execution_count' => (int) ($aggregate?->scheduled_execution_count ?? 0),
            'command_execution_count' => (int) ($aggregate?->command_execution_count ?? 0),
            'candidate_count_sum' => (int) ($aggregate?->candidate_count_sum ?? 0),
            'deleted_count_sum' => (int) ($aggregate?->deleted_count_sum ?? 0),
            'average_duration_ms' => $averageDuration === null
                ? null
                : (int) round((float) $averageDuration),
            'oldest_started_at' => $this->serializeSummaryTimestamp(
                $aggregate?->oldest_started_at
            ),
            'newest_started_at' => $this->serializeSummaryTimestamp(
                $aggregate?->newest_started_at
            ),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function normalizedSummaryFilters(array $filters): array
    {
        $normalized = [];

        foreach ([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ] as $filter) {
            if (
                ! array_key_exists($filter, $filters)
                || $filters[$filter] === null
                || $filters[$filter] === ''
            ) {
                continue;
            }

            $normalized[$filter] = $filter === 'actor_user_id'
                ? (int) $filters[$filter]
                : (string) $filters[$filter];
        }

        ksort($normalized);

        return $normalized;
    }

    private function summaryCacheKey(
        array $filters,
        string $generation
    ): string {
        $encoded = json_encode(
            $filters,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
        );

        return self::SUMMARY_CACHE_KEY_PREFIX
            . ':'
            . hash('sha256', $generation . '|' . $encoded);
    }

    private function summaryCacheGeneration(): string
    {
        try {
            $generation = Cache::get(
                self::SUMMARY_CACHE_GENERATION_KEY
            );

            return is_string($generation) && $generation !== ''
                ? $generation
                : self::SUMMARY_CACHE_DEFAULT_GENERATION;
        } catch (Throwable $exception) {
            $this->observe(
                'warning',
                self::SUMMARY_CACHE_EVENT_GENERATION_READ_FALLBACK,
                [
                    'cache_key_prefix' =>
                        self::SUMMARY_CACHE_KEY_PREFIX,
                    'generation_present' => false,
                    'fallback_reason_class' =>
                        $exception::class,
                ]
            );

            return self::SUMMARY_CACHE_DEFAULT_GENERATION;
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
            // Observability must never change Summary behavior.
        }
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): Builder {
        foreach (['type', 'status', 'actor_user_id'] as $filter) {
            if (
                array_key_exists($filter, $filters)
                && $filters[$filter] !== null
                && $filters[$filter] !== ''
            ) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['started_from'])) {
            $query->where(
                'started_at',
                '>=',
                $filters['started_from']
            );
        }

        if (! empty($filters['started_to'])) {
            $query->where(
                'started_at',
                '<=',
                $filters['started_to']
            );
        }

        return $query;
    }

    private function serializeSummaryTimestamp(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }

    public function assertWithinLimit(Builder $query, int $maximumRows, string $format): int
    {
        $count = (clone $query)->count();

        if ($count > $maximumRows) {
            throw ValidationException::withMessages([
                'export' => sprintf(
                    'The %s export exceeds the maximum of %d rows.',
                    strtoupper($format),
                    $maximumRows
                ),
            ]);
        }

        return $count;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function serialize(
        ReportSavedViewShareActivityRetentionExecution $execution
    ): array {
        $row = [];

        foreach (self::COLUMNS as $column) {
            $value = $execution->getAttribute($column);

            if ($value instanceof Carbon) {
                $value = $value->toISOString();
            }

            $row[$column] = $value;
        }

        return $row;
    }

    public function logExport(
        ?int $actorUserId,
        string $format,
        array $filters,
        int $exportedCount,
        int $durationMs
    ): void {
        Log::info('Saved view retention execution history exported.', [
            'actor_user_id' => $actorUserId,
            'format' => $format,
            'filters' => $filters,
            'exported_count' => $exportedCount,
            'duration_ms' => $durationMs,
        ]);
    }
}
