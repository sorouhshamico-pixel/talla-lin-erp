<?php

namespace App\Support\Reports;

use InvalidArgumentException;

class ReportSavedViewDiagnosticSnapshotExporter
{
    public const DIRECTORY = 'report-saved-view-diagnostics';

    public const MANIFEST_FILENAME = 'manifest.json';

    /**
     * @return array<string, string>
     */
    public static function export(string $format = 'markdown', ?string $filename = null): array
    {
        $format = self::normalizeFormat($format);
        $extension = $format === 'json' ? 'json' : 'md';
        $filename ??= 'report-saved-view-diagnostics.'.$extension;

        self::ensureDirectoryExists();

        $relativePath = self::DIRECTORY.'/'.$filename;
        $absolutePath = storage_path('app/'.$relativePath);

        $contents = $format === 'json'
            ? ReportSavedViewRegistryDiagnosticReport::json()
            : ReportSavedViewRegistryDiagnosticReport::markdown();

        file_put_contents($absolutePath, $contents.PHP_EOL);

        $snapshot = [
            'format' => $format,
            'filename' => $filename,
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'manifest_relative_path' => self::manifestRelativePath(),
            'manifest_absolute_path' => self::manifestPath(),
        ];

        self::writeManifest($snapshot);

        return $snapshot;
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

    /**
     * @return array<string, mixed>
     */
    public static function pruneSnapshots(bool $includeManifest = false): array
    {
        $directory = storage_path('app/'.self::DIRECTORY);

        if (! is_dir($directory)) {
            return [
                'directory' => self::DIRECTORY,
                'deleted_count' => 0,
                'deleted_files' => [],
                'manifest_preserved' => ! $includeManifest,
            ];
        }

        $deletedFiles = [];

        foreach (glob($directory.'/*') ?: [] as $path) {
            if (! is_file($path)) {
                continue;
            }

            $filename = basename($path);

            if ($filename === self::MANIFEST_FILENAME && ! $includeManifest) {
                continue;
            }

            unlink($path);

            $deletedFiles[] = self::DIRECTORY.'/'.$filename;
        }

        return [
            'directory' => self::DIRECTORY,
            'deleted_count' => count($deletedFiles),
            'deleted_files' => $deletedFiles,
            'manifest_preserved' => ! $includeManifest,
        ];
    }

    public static function manifestPath(): string
    {
        return storage_path('app/'.self::manifestRelativePath());
    }

    public static function manifestRelativePath(): string
    {
        return self::DIRECTORY.'/'.self::MANIFEST_FILENAME;
    }

    /**
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        $path = self::manifestPath();

        if (! file_exists($path)) {
            return self::emptyManifest();
        }

        $decoded = json_decode(file_get_contents($path) ?: '', true);

        if (! is_array($decoded)) {
            return self::emptyManifest();
        }

        return $decoded;
    }

    /**
     * @param  array<string, string>  $snapshot
     */
    private static function writeManifest(array $snapshot): void
    {
        self::ensureDirectoryExists();

        $summary = ReportSavedViewRegistryDiagnosticReport::summary();

        $manifest = self::manifest();

        $entry = [
            'format' => $snapshot['format'],
            'filename' => $snapshot['filename'],
            'relative_path' => $snapshot['relative_path'],
            'exported_at' => now()->toIso8601String(),
            'healthy' => (bool) $summary['valid'],
            'report_count' => (int) $summary['report_count'],
            'invalid_count' => (int) $summary['invalid_count'],
        ];

        $manifest['updated_at'] = $entry['exported_at'];
        $manifest['latest'][$snapshot['format']] = $entry;
        $manifest['history'][] = $entry;
        $manifest['history'] = array_slice($manifest['history'], -50);

        file_put_contents(
            self::manifestPath(),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyManifest(): array
    {
        return [
            'directory' => self::DIRECTORY,
            'updated_at' => null,
            'latest' => [],
            'history' => [],
        ];
    }

    private static function ensureDirectoryExists(): void
    {
        $absoluteDirectory = storage_path('app/'.self::DIRECTORY);

        if (! is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }
    }

    private static function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));

        if (! in_array($format, ['markdown', 'json'], true)) {
            throw new InvalidArgumentException('Unsupported report saved view diagnostic snapshot format ['.$format.'].');
        }

        return $format;
    }
}
