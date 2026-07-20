<?php

namespace App\Services;

use App\Models\ReportSavedViewShareActivityRetentionExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportSavedViewShareActivityRetentionExecutionHistoryExportService
{
    public const CSV_MAXIMUM_ROWS = 100000;
    public const JSON_MAXIMUM_ROWS = 10000;

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
        $query = ReportSavedViewShareActivityRetentionExecution::query()
            ->select(self::COLUMNS)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        foreach (['type', 'status', 'actor_user_id'] as $filter) {
            if (array_key_exists($filter, $filters) && $filters[$filter] !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['started_from'])) {
            $query->where('started_at', '>=', $filters['started_from']);
        }

        if (! empty($filters['started_to'])) {
            $query->where('started_at', '<=', $filters['started_to']);
        }

        return $query;
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
