<?php

namespace App\Support\Reports;

final class ReportSavedViewCsvImportParser
{
    /**
     * @return array{
     *     headers: array<int, string>,
     *     header_errors: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int
     * }
     */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return $this->emptyResult('تعذر قراءة ملف CSV.');
        }

        $headers = fgetcsv($handle);

        if (! is_array($headers)) {
            fclose($handle);

            return $this->emptyResult('ملف CSV فارغ أو غير صالح.');
        }

        $headers = array_map(
            fn ($header): string => trim(
                str_replace("\xEF\xBB\xBF", '', (string) $header)
            ),
            $headers
        );

        $formatVersionColumn =
            ReportSavedViewImportExportVersionRegistry::formatVersionColumn();
        $hasExplicitFormatVersion = in_array(
            $formatVersionColumn,
            $headers,
            true
        );
        $requiredColumns = $hasExplicitFormatVersion
            ? ReportSavedViewImportExportVersionRegistry::requiredColumns(
                ReportSavedViewImportExportVersionRegistry::currentVersion()
            )
            : ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns();
        $missingColumns = array_values(array_diff($requiredColumns, $headers));
        $rows = [];
        $rowNumber = 1;
        $encounteredFormatVersions = [];

        if ($missingColumns !== []) {
            fclose($handle);

            return [
                'headers' => $headers,
                'header_errors' => [
                    'الأعمدة المطلوبة غير موجودة: '
                    . implode(', ', $missingColumns),
                ],
                'rows' => [],
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
            ];
        }

        $indexes = array_flip($headers);

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = [];

            foreach (
                ReportSavedViewImportExportVersionRegistry::legacyRequiredColumns()
                as $column
            ) {
                $data[$column] = trim(
                    (string) ($row[$indexes[$column]] ?? '')
                );
            }

            $data['filters_payload'] =
                array_key_exists('filters_payload', $indexes)
                    ? trim(
                        (string) (
                            $row[$indexes['filters_payload']] ?? ''
                        )
                    )
                    : '';
            $data['format_version'] = $hasExplicitFormatVersion
                ? trim(
                    (string) (
                        $row[$indexes[$formatVersionColumn]] ?? ''
                    )
                )
                : '';

            $errors = [];
            $name = $data['name'];
            $reportKey = $data['report_key'];
            $filterCount = $data['filter_count'];
            $isDefault = mb_strtolower(
                $data['is_default'],
                'UTF-8'
            );
            $formatVersion = $data['format_version'];

            if ($hasExplicitFormatVersion) {
                if ($formatVersion === '') {
                    $errors[] = 'قيمة format_version مطلوبة.';
                } else {
                    $encounteredFormatVersions[$formatVersion] = true;

                    if (
                        ! ReportSavedViewImportExportVersionRegistry::supports(
                            $formatVersion
                        )
                    ) {
                        $errors[] =
                            'إصدار تنسيق ملف الاستيراد غير مدعوم.';
                    }
                }

                if (
                    ReportSavedViewImportExportVersionRegistry::
                        requiresFiltersPayload($formatVersion)
                    && $data['filters_payload'] === ''
                ) {
                    $errors[] =
                        'filters_payload مطلوب في الإصدار 1.';
                }
            }

            if ($name === '') {
                $errors[] = 'اسم العرض مطلوب.';
            } elseif (mb_strlen($name, 'UTF-8') > 120) {
                $errors[] = 'اسم العرض يتجاوز 120 حرفًا.';
            }

            if ($reportKey === '') {
                $errors[] = 'مفتاح التقرير مطلوب.';
            } elseif (! ReportSavedViewRegistry::has($reportKey)) {
                $errors[] = 'مفتاح التقرير غير معروف.';
            }

            if (
                $isDefault !== ''
                && ! in_array(
                    $isDefault,
                    [
                        'yes',
                        'no',
                        '1',
                        '0',
                        'true',
                        'false',
                        'نعم',
                        'لا',
                    ],
                    true
                )
            ) {
                $errors[] = 'قيمة الافتراضي غير صالحة.';
            }

            if (
                $filterCount !== ''
                && (
                    ! ctype_digit($filterCount)
                    || (int) $filterCount < 0
                )
            ) {
                $errors[] =
                    'عدد الفلاتر يجب أن يكون رقمًا صحيحًا.';
            }

            $filters = $this->decodeFiltersPayload(
                $data['filters_payload'],
                $errors
            );

            $rows[] = [
                'row_number' => $rowNumber,
                'format_version' => $hasExplicitFormatVersion
                    ? $formatVersion
                    : null,
                'name' => $name,
                'report_label' =>
                    ReportSavedViewRegistry::find($reportKey)['label']
                    ?? $data['report_label'],
                'report_key' => $reportKey,
                'is_default' => in_array(
                    $isDefault,
                    ['yes', '1', 'true', 'نعم'],
                    true
                ) ? 'نعم' : 'لا',
                'filter_count' => $filterCount === ''
                    ? 0
                    : (int) $filterCount,
                'filters_summary' => $data['filters_summary'],
                'filters_payload' => $data['filters_payload'],
                'filters' => $filters,
                'status' => $errors === [] ? 'valid' : 'invalid',
                'errors' => $errors,
            ];
        }

        fclose($handle);

        $headerErrors = [];

        if (
            $hasExplicitFormatVersion
            && count($encounteredFormatVersions) > 1
        ) {
            $headerErrors[] =
                'يحتوي الملف على أكثر من إصدار format_version.';
        }

        $validRows = count(
            array_filter(
                $rows,
                fn (array $row): bool => $row['status'] === 'valid'
            )
        );

        return [
            'headers' => $headers,
            'header_errors' => $headerErrors,
            'rows' => $rows,
            'total_rows' => count($rows),
            'valid_rows' => $validRows,
            'invalid_rows' => count($rows) - $validRows,
        ];
    }

    /**
     * @return array{
     *     headers: array<int, string>,
     *     header_errors: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int
     * }
     */
    private function emptyResult(string $error): array
    {
        return [
            'headers' => [],
            'header_errors' => [$error],
            'rows' => [],
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
        ];
    }

    /**
     * @param array<int, string> $errors
     * @return array<string, mixed>
     */
    private function decodeFiltersPayload(
        string $filtersPayload,
        array &$errors
    ): array {
        if ($filtersPayload === '') {
            return [];
        }

        $decodedObject = json_decode($filtersPayload);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || ! $decodedObject instanceof \stdClass
        ) {
            $errors[] =
                'filters_payload يجب أن يكون JSON object صالحًا.';

            return [];
        }

        $decodedFilters = json_decode($filtersPayload, true);

        if (! is_array($decodedFilters)) {
            $errors[] =
                'filters_payload يجب أن يكون JSON object صالحًا.';

            return [];
        }

        return $this->cleanFilters($decodedFilters);
    }

    /**
     * @param array<mixed> $filters
     * @return array<string, mixed>
     */
    private function cleanFilters(array $filters): array
    {
        $cleaned = [];

        foreach ($filters as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->cleanFilters($value);

                if ($value === []) {
                    continue;
                }
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    /**
     * @param array<int, mixed> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
