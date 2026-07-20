<?php

namespace App\Http\Controllers;

use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSavedViewShareActivityRetentionExecutionHistoryExportController
    extends Controller
{
    public function csv(
        Request $request,
        ReportSavedViewShareActivityRetentionExecutionHistoryExportService $exporter
    ): StreamedResponse {
        $startedAt = hrtime(true);
        $filters = $exporter->validatedFilters($request->only([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ]));
        $query = $exporter->query($filters);
        $count = $exporter->assertWithinLimit(
            $query,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::CSV_MAXIMUM_ROWS,
            'csv'
        );

        $filename = 'saved-view-retention-execution-history-'
            . now()->format('Ymd-His')
            . '.csv';

        $response = response()->streamDownload(
            function () use ($query, $exporter): void {
                $output = fopen('php://output', 'wb');

                if ($output === false) {
                    throw new \RuntimeException('Unable to open CSV output stream.');
                }

                fwrite($output, "\xEF\xBB\xBF");
                fputcsv(
                    $output,
                    ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS,
                    ',',
                    '"',
                    '\\',
                    "\r\n"
                );

                $query->chunkById(
                    1000,
                    function ($executions) use ($output, $exporter): void {
                        foreach ($executions as $execution) {
                            $row = $exporter->serialize($execution);
                            fputcsv(
                                $output,
                                array_map(
                                    static fn (string $column): mixed => $row[$column],
                                    ReportSavedViewShareActivityRetentionExecutionHistoryExportService::COLUMNS
                                ),
                                ',',
                                '"',
                                '\\',
                                "\r\n"
                            );
                        }
                    },
                    'id'
                );

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );

        $exporter->logExport(
            $request->user()?->getAuthIdentifier(),
            'csv',
            $filters,
            $count,
            (int) round((hrtime(true) - $startedAt) / 1_000_000)
        );

        return $response;
    }

    public function json(
        Request $request,
        ReportSavedViewShareActivityRetentionExecutionHistoryExportService $exporter
    ): JsonResponse {
        $startedAt = hrtime(true);
        $filters = $exporter->validatedFilters($request->only([
            'type',
            'status',
            'actor_user_id',
            'started_from',
            'started_to',
        ]));
        $query = $exporter->query($filters);
        $count = $exporter->assertWithinLimit(
            $query,
            ReportSavedViewShareActivityRetentionExecutionHistoryExportService::JSON_MAXIMUM_ROWS,
            'json'
        );

        $items = $query
            ->get()
            ->map(
                fn ($execution): array => $exporter->serialize($execution)
            )
            ->values()
            ->all();

        $exporter->logExport(
            $request->user()?->getAuthIdentifier(),
            'json',
            $filters,
            $count,
            (int) round((hrtime(true) - $startedAt) / 1_000_000)
        );

        return response()->json([
            'exported_at' => now()->toISOString(),
            'filters' => $filters,
            'count' => $count,
            'items' => $items,
        ]);
    }
}
