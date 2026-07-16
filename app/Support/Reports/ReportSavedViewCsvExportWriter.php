<?php

namespace App\Support\Reports;

final class ReportSavedViewCsvExportWriter
{
    public function write(iterable $formattedSavedViews): void
    {
        $handle = fopen('php://output', 'w');

        if ($handle === false) {
            return;
        }

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv(
            $handle,
            ReportSavedViewImportExportVersionRegistry::exportHeader()
        );

        foreach ($formattedSavedViews as $formatted) {
            $filterCount = 0;
            $summaryParts = [];
            $filtersPayloadData = [];

            foreach (($formatted->filters ?? []) as $filter) {
                if (! is_array($filter)) {
                    continue;
                }

                $filterCount++;

                $displayValue = (string) (
                    $filter['display_value'] ?? ''
                );
                $rawValue = $this->stringifyFilterValue(
                    $filter['value'] ?? ''
                );

                if (
                    $rawValue !== ''
                    && $rawValue !== $displayValue
                ) {
                    $summaryParts[] =
                        $filter['label'] . ': ' . $displayValue
                        . ' (' . $rawValue . ')';
                } else {
                    $summaryParts[] =
                        $filter['label'] . ': ' . $displayValue;
                }

                $key = (string) ($filter['key'] ?? '');

                if ($key !== '') {
                    $filtersPayloadData[$key] =
                        $filter['value'] ?? null;
                }
            }

            $filtersSummary = implode('; ', $summaryParts);
            $filtersPayload = json_encode(
                (object) $filtersPayloadData,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
            );

            $updatedAt = $formatted->updated_at ?? null;
            $updatedAtValue = (
                is_object($updatedAt)
                && method_exists($updatedAt, 'toDateTimeString')
            )
                ? $updatedAt->toDateTimeString()
                : '';

            fputcsv($handle, [
                ReportSavedViewImportExportVersionRegistry::currentVersion(),
                (string) ($formatted->name ?? ''),
                (string) ($formatted->report_label ?? ''),
                (string) ($formatted->report_key ?? ''),
                ($formatted->is_default ?? false) ? 'yes' : 'no',
                $filterCount,
                $filtersSummary,
                $filtersPayload === false ? '{}' : $filtersPayload,
                $updatedAtValue,
            ]);
        }

        fclose($handle);
    }

    private function stringifyFilterValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        return $encoded === false ? '' : $encoded;
    }
}
