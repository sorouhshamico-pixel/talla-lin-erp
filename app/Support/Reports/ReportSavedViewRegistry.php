<?php

namespace App\Support\Reports;

class ReportSavedViewRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function reports(): array
    {
        return [
            'sales-invoice-aging' => [
                'key' => 'sales-invoice-aging',
                'label' => 'تقرير أعمار ذمم فواتير المبيعات',
                'view' => 'reports.sales-invoice-aging',
                'view_path' => 'resources/views/reports/sales-invoice-aging.blade.php',
                'index_route' => 'reports.sales-invoice-aging.index',
                'export_route' => 'reports.sales-invoice-aging.export',
                'saved_view_store_route' => 'reports.sales-invoice-aging.saved-views.store',
                'config_partial' => 'reports.partials.sales-invoice-aging-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'customer_id',
                    'payment_status',
                    'aging_bucket',
                ],
                'test_ids' => [
                    'section_card' => 'sales-invoice-aging-saved-views-selector',
                    'form_card' => 'sales-invoice-aging-save-view-card',
                    'form' => 'sales-invoice-aging-save-view-form',
                    'name_input' => 'sales-invoice-aging-saved-view-name-input',
                    'default_checkbox' => 'sales-invoice-aging-saved-view-default-checkbox',
                    'save_button' => 'sales-invoice-aging-save-view-button',
                ],
            ],
            'customer-sales-invoice-aging' => [
                'key' => 'customer-sales-invoice-aging',
                'label' => 'تقرير أعمار ذمم العملاء',
                'view' => 'reports.customer-sales-invoice-aging',
                'view_path' => 'resources/views/reports/customer-sales-invoice-aging.blade.php',
                'index_route' => 'reports.customer-sales-invoice-aging.index',
                'export_route' => 'reports.customer-sales-invoice-aging.export',
                'saved_view_store_route' => 'reports.customer-sales-invoice-aging.saved-views.store',
                'config_partial' => 'reports.partials.customer-sales-invoice-aging-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'customer_id',
                    'aging_bucket',
                ],
                'test_ids' => [
                    'section_card' => 'customer-aging-saved-views-selector',
                    'form_card' => 'customer-aging-save-view-card',
                    'form' => 'customer-aging-save-view-form',
                    'name_input' => 'customer-aging-saved-view-name-input',
                    'default_checkbox' => 'customer-aging-saved-view-default-checkbox',
                    'save_button' => 'customer-aging-save-view-button',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $key): ?array
    {
        return self::reports()[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return self::find($key) !== null;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::reports());
    }

    public static function count(): int
    {
        return count(self::reports());
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return self::pluckStringField('label');
    }

    /**
     * @return array<string, string>
     */
    public static function viewPaths(): array
    {
        return self::pluckStringField('view_path');
    }

    /**
     * @return array<string, string>
     */
    public static function configPartials(): array
    {
        return self::pluckStringField('config_partial');
    }

    /**
     * @return array<string, string>
     */
    public static function configPartialPaths(): array
    {
        return self::pluckStringField('config_partial_path');
    }

    /**
     * @return array<string, string>
     */
    public static function indexRoutes(): array
    {
        return self::pluckStringField('index_route');
    }

    /**
     * @return array<string, string>
     */
    public static function exportRoutes(): array
    {
        return self::pluckStringField('export_route');
    }

    /**
     * @return array<string, string>
     */
    public static function savedViewStoreRoutes(): array
    {
        return self::pluckStringField('saved_view_store_route');
    }

    /**
     * @return array<int, string>
     */
    public static function hiddenFields(string $key): array
    {
        $report = self::find($key);

        if (! $report) {
            return [];
        }

        return $report['hidden_fields'] ?? [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function hiddenFieldMap(): array
    {
        return array_map(
            fn (array $report): array => $report['hidden_fields'] ?? [],
            self::reports()
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function testIdMap(): array
    {
        return array_map(
            fn (array $report): array => $report['test_ids'] ?? [],
            self::reports()
        );
    }

    public static function configPartial(string $key): ?string
    {
        $report = self::find($key);

        if (! $report) {
            return null;
        }

        return $report['config_partial'] ?? null;
    }

    public static function configPartialPath(string $key): ?string
    {
        $report = self::find($key);

        if (! $report) {
            return null;
        }

        return $report['config_partial_path'] ?? null;
    }

    public static function indexRoute(string $key): ?string
    {
        $report = self::find($key);

        if (! $report) {
            return null;
        }

        return $report['index_route'] ?? null;
    }

    public static function savedViewStoreRoute(string $key): ?string
    {
        $report = self::find($key);

        if (! $report) {
            return null;
        }

        return $report['saved_view_store_route'] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function documentationRows(): array
    {
        return array_values(array_map(
            fn (array $report): array => [
                'key' => $report['key'],
                'label' => $report['label'],
                'view_path' => $report['view_path'],
                'index_route' => $report['index_route'],
                'saved_view_store_route' => $report['saved_view_store_route'],
                'config_partial_path' => $report['config_partial_path'],
                'hidden_fields' => $report['hidden_fields'],
            ],
            self::reports()
        ));
    }

    /**
     * @return array<string, string>
     */
    private static function pluckStringField(string $field): array
    {
        $values = [];

        foreach (self::reports() as $key => $report) {
            if (isset($report[$field]) && is_string($report[$field])) {
                $values[$key] = $report[$field];
            }
        }

        return $values;
    }
}
