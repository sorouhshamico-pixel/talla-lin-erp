<?php

namespace App\Support\Reports;

final class ReportSavedViewImportExportVersionRegistry
{
    private const FORMAT_VERSION_COLUMN = 'format_version';

    private const CURRENT_VERSION = '1';

    private const LEGACY_MODE = 'legacy_unversioned';

    private const LEGACY_REQUIRED_COLUMNS = [
        'name',
        'report_label',
        'report_key',
        'is_default',
        'filter_count',
        'filters_summary',
        'updated_at',
    ];

    private const VERSIONS = [
        '1' => [
            'required_columns' => [
                'format_version',
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'filters_payload',
                'updated_at',
            ],
            'requires_filters_payload' => true,
        ],
    ];

    private function __construct()
    {
    }

    public static function formatVersionColumn(): string
    {
        return self::FORMAT_VERSION_COLUMN;
    }

    public static function currentVersion(): string
    {
        return self::CURRENT_VERSION;
    }

    /**
     * @return array<int, string>
     */
    public static function supportedVersions(): array
    {
        return [self::CURRENT_VERSION];
    }

    public static function supports(string $version): bool
    {
        return array_key_exists($version, self::VERSIONS);
    }

    /**
     * @return array<int, string>
     */
    public static function legacyRequiredColumns(): array
    {
        return self::LEGACY_REQUIRED_COLUMNS;
    }

    /**
     * @return array<int, string>
     */
    public static function requiredColumns(string $version): array
    {
        return self::VERSIONS[$version]['required_columns'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function exportHeader(): array
    {
        return self::requiredColumns(self::currentVersion());
    }

    public static function requiresFiltersPayload(string $version): bool
    {
        return (bool) (self::VERSIONS[$version]['requires_filters_payload'] ?? false);
    }
}
