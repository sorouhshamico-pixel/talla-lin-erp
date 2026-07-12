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

    public static function configPartial(string $key): ?string
    {
        $report = self::find($key);

        if (! $report) {
            return null;
        }

        return $report['config_partial'] ?? null;
    }
}
