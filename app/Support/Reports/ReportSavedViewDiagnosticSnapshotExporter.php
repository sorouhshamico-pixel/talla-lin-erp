<?php

namespace App\Support\Reports;

use InvalidArgumentException;

class ReportSavedViewDiagnosticSnapshotExporter
{
    public const DIRECTORY = 'report-saved-view-diagnostics';

    /**
     * @return array<string, string>
     */
    public static function export(string $format = 'markdown', ?string $filename = null): array
    {
        $format = strtolower(trim($format));

        if (! in_array($format, ['markdown', 'json'], true)) {
            throw new InvalidArgumentException('Unsupported report saved view diagnostic snapshot format ['.$format.'].');
        }

        $extension = $format === 'json' ? 'json' : 'md';
        $filename ??= 'report-saved-view-diagnostics.'.$extension;

        $relativePath = self::DIRECTORY.'/'.$filename;
        $absoluteDirectory = storage_path('app/'.self::DIRECTORY);
        $absolutePath = storage_path('app/'.$relativePath);

        if (! is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }

        $contents = $format === 'json'
            ? ReportSavedViewRegistryDiagnosticReport::json()
            : ReportSavedViewRegistryDiagnosticReport::markdown();

        file_put_contents($absolutePath, $contents.PHP_EOL);

        return [
            'format' => $format,
            'filename' => $filename,
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function exportMarkdown(?string $filename = null): array
    {
        return self::export('markdown', $filename);
    }

    /**
     * @return array<string, string>
     */
    public static function exportJson(?string $filename = null): array
    {
        return self::export('json', $filename);
    }
}
