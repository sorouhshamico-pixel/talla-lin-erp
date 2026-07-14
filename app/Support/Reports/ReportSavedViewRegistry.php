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
            'customer-sales-invoice-aging-drilldown' => [
                'key' => 'customer-sales-invoice-aging-drilldown',
                'label' => 'تفاصيل فواتير العملاء المفتوحة',
                'view' => 'reports.customer-sales-invoice-aging-drilldown',
                'view_path' => 'resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php',
                'index_route' => 'reports.customer-sales-invoice-aging.drilldown',
                'export_route' => 'reports.customer-sales-invoice-aging.drilldown.export',
                'saved_view_store_route' => 'reports.customer-sales-invoice-aging.drilldown.saved-views.store',
                'config_partial' => 'reports.partials.customer-sales-invoice-aging-drilldown-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/customer-sales-invoice-aging-drilldown-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'customer_id',
                    'branch_id',
                    'as_of_date',
                    'aging_bucket',
                ],
                'test_ids' => [
                    'section_card' => 'customer-aging-drilldown-saved-views-selector',
                    'empty' => 'customer-aging-drilldown-saved-views-empty',
                    'form_card' => 'customer-aging-drilldown-save-view-card',
                    'form' => 'customer-aging-drilldown-save-view-form',
                    'name_input' => 'customer-aging-drilldown-saved-view-name-input',
                    'default_checkbox' => 'customer-aging-drilldown-saved-view-default-checkbox',
                    'save_button' => 'customer-aging-drilldown-save-view-button',
                    'list' => 'customer-aging-drilldown-saved-views-list',
                    'row' => 'customer-aging-drilldown-saved-view-row',
                    'open_link' => 'customer-aging-drilldown-saved-view-open-link',
                    'active_badge' => 'customer-aging-drilldown-saved-view-active-badge',
                    'default_badge' => 'customer-aging-drilldown-saved-view-default-badge',
                ],
            ],
            'supplier-purchase-invoice-aging' => [
                'key' => 'supplier-purchase-invoice-aging',
                'label' => 'تقرير أعمار ذمم الموردين',
                'view' => 'reports.supplier-purchase-invoice-aging',
                'view_path' => 'resources/views/reports/supplier-purchase-invoice-aging.blade.php',
                'index_route' => 'reports.supplier-purchase-invoice-aging.index',
                'export_route' => 'reports.supplier-purchase-invoice-aging.export',
                'saved_view_store_route' => 'reports.supplier-purchase-invoice-aging.saved-views.store',
                'config_partial' => 'reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'supplier_id',
                    'aging_bucket',
                ],
                'test_ids' => [
                    'section_card' => 'supplier-aging-saved-views-selector',
                    'empty' => 'supplier-aging-saved-views-empty',
                    'form_card' => 'supplier-aging-save-view-card',
                    'form' => 'supplier-aging-save-view-form',
                    'name_input' => 'supplier-aging-saved-view-name-input',
                    'default_checkbox' => 'supplier-aging-saved-view-default-checkbox',
                    'save_button' => 'supplier-aging-save-view-button',
                    'list' => 'supplier-aging-saved-views-list',
                    'item' => 'supplier-aging-saved-view-item',
                    'open_link' => 'supplier-aging-saved-view-open-link',
                    'active_badge' => 'supplier-aging-saved-view-active-badge',
                    'default_badge' => 'supplier-aging-saved-view-default-badge',
                ],
            ],
            'supplier-purchase-invoice-aging-drilldown' => [
                'key' => 'supplier-purchase-invoice-aging-drilldown',
                'label' => 'تفاصيل فواتير الموردين المفتوحة',
                'view' => 'reports.supplier-purchase-invoice-aging-drilldown',
                'view_path' => 'resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php',
                'index_route' => 'reports.supplier-purchase-invoice-aging.drilldown',
                'export_route' => 'reports.supplier-purchase-invoice-aging.drilldown.export',
                'saved_view_store_route' => 'reports.supplier-purchase-invoice-aging.drilldown.saved-views.store',
                'config_partial' => 'reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'supplier_id',
                    'branch_id',
                    'as_of_date',
                    'aging_bucket',
                ],
                'test_ids' => [
                    'section_card' => 'supplier-aging-drilldown-saved-views-selector',
                    'empty' => 'supplier-aging-drilldown-saved-views-empty',
                    'form_card' => 'supplier-aging-drilldown-save-view-card',
                    'form' => 'supplier-aging-drilldown-save-view-form',
                    'name_input' => 'supplier-aging-drilldown-saved-view-name-input',
                    'default_checkbox' => 'supplier-aging-drilldown-saved-view-default-checkbox',
                    'save_button' => 'supplier-aging-drilldown-save-view-button',
                    'list' => 'supplier-aging-drilldown-saved-views-list',
                    'item' => 'supplier-aging-drilldown-saved-view-item',
                    'open_link' => 'supplier-aging-drilldown-saved-view-open-link',
                    'active_badge' => 'supplier-aging-drilldown-saved-view-active-badge',
                    'default_badge' => 'supplier-aging-drilldown-saved-view-default-badge',
                ],
            ],
            'cash-flow-dashboard' => [
                'key' => 'cash-flow-dashboard',
                'label' => 'لوحة التدفق النقدي المتوقع',
                'view' => 'reports.cash-flow-dashboard',
                'view_path' => 'resources/views/reports/cash-flow-dashboard.blade.php',
                'index_route' => 'reports.cash-flow-dashboard.index',
                'export_route' => 'reports.cash-flow-dashboard.export',
                'saved_view_store_route' => 'reports.cash-flow-dashboard.saved-views.store',
                'config_partial' => 'reports.partials.cash-flow-dashboard-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/cash-flow-dashboard-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'branch_id',
                    'date_from',
                    'date_to',
                ],
                'test_ids' => [
                    'section_card' => 'cash-flow-dashboard-saved-views-selector',
                    'empty' => 'cash-flow-dashboard-saved-views-empty',
                    'form_card' => 'cash-flow-dashboard-save-view-card',
                    'form' => 'cash-flow-dashboard-save-view-form',
                    'name_input' => 'cash-flow-dashboard-saved-view-name-input',
                    'default_checkbox' => 'cash-flow-dashboard-saved-view-default-checkbox',
                    'save_button' => 'cash-flow-dashboard-save-view-button',
                    'list' => 'cash-flow-dashboard-saved-views-list',
                    'item' => 'cash-flow-dashboard-saved-view-item',
                    'open_link' => 'cash-flow-dashboard-saved-view-open-link',
                    'active_badge' => 'cash-flow-dashboard-saved-view-active-badge',
                    'default_badge' => 'cash-flow-dashboard-saved-view-default-badge',
                ],
            ],
            'index' => [
                'key' => 'index',
                'label' => 'التقارير المالية الأساسية',
                'view' => 'reports.index',
                'view_path' => 'resources/views/reports/index.blade.php',
                'index_route' => 'reports.index',
                'export_route' => 'reports.index',
                'saved_view_store_route' => 'reports.index.saved-views.store',
                'config_partial' => 'reports.partials.index-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/index-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'from_date',
                    'to_date',
                    'branch_id',
                    'expense_category_id',
                    'payment_method',
                ],
                'test_ids' => [
                    'section_card' => 'reports-index-saved-views-selector',
                    'empty' => 'reports-index-saved-views-empty',
                    'form_card' => 'reports-index-save-view-card',
                    'form' => 'reports-index-save-view-form',
                    'name_input' => 'reports-index-saved-view-name-input',
                    'default_checkbox' => 'reports-index-saved-view-default-checkbox',
                    'save_button' => 'reports-index-save-view-button',
                    'list' => 'reports-index-saved-views-list',
                    'item' => 'reports-index-saved-view-item',
                    'open_link' => 'reports-index-saved-view-open-link',
                    'active_badge' => 'reports-index-saved-view-active-badge',
                    'default_badge' => 'reports-index-saved-view-default-badge',
                ],
            ],
            'profit-loss' => [
                'key' => 'profit-loss',
                'label' => 'تقرير الأرباح والخسائر',
                'view' => 'reports.profit-loss',
                'view_path' => 'resources/views/reports/profit-loss.blade.php',
                'index_route' => 'reports.profit-loss',
                'export_route' => 'reports.profit-loss.export',
                'saved_view_store_route' => 'reports.profit-loss.saved-views.store',
                'config_partial' => 'reports.partials.profit-loss-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/profit-loss-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'from_date',
                    'to_date',
                    'branch_id',
                ],
                'test_ids' => [
                    'section_card' => 'profit-loss-saved-views-selector',
                    'empty' => 'profit-loss-saved-views-empty',
                    'form_card' => 'profit-loss-save-view-card',
                    'form' => 'profit-loss-save-view-form',
                    'name_input' => 'profit-loss-saved-view-name-input',
                    'default_checkbox' => 'profit-loss-saved-view-default-checkbox',
                    'save_button' => 'profit-loss-save-view-button',
                    'list' => 'profit-loss-saved-views-list',
                    'item' => 'profit-loss-saved-view-item',
                    'open_link' => 'profit-loss-saved-view-open-link',
                    'active_badge' => 'profit-loss-saved-view-active-badge',
                    'default_badge' => 'profit-loss-saved-view-default-badge',
                ],
            ],
            'receivable-payable-aging-dashboard' => [
                'key' => 'receivable-payable-aging-dashboard',
                'label' => 'لوحة أعمار الذمم',
                'view' => 'reports.receivable-payable-aging-dashboard',
                'view_path' => 'resources/views/reports/receivable-payable-aging-dashboard.blade.php',
                'index_route' => 'reports.receivable-payable-aging-dashboard.index',
                'export_route' => 'reports.receivable-payable-aging-dashboard.export',
                'saved_view_store_route' => 'reports.receivable-payable-aging-dashboard.saved-views.store',
                'config_partial' => 'reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'branch_id',
                    'as_of_date',
                ],
                'test_ids' => [
                    'section_card' => 'receivable-payable-aging-dashboard-saved-views-selector',
                    'empty' => 'receivable-payable-aging-dashboard-saved-views-empty',
                    'form_card' => 'receivable-payable-aging-dashboard-save-view-card',
                    'form' => 'receivable-payable-aging-dashboard-save-view-form',
                    'name_input' => 'receivable-payable-aging-dashboard-saved-view-name-input',
                    'default_checkbox' => 'receivable-payable-aging-dashboard-saved-view-default-checkbox',
                    'save_button' => 'receivable-payable-aging-dashboard-save-view-button',
                    'list' => 'receivable-payable-aging-dashboard-saved-views-list',
                    'item' => 'receivable-payable-aging-dashboard-saved-view-item',
                    'open_link' => 'receivable-payable-aging-dashboard-saved-view-open-link',
                    'active_badge' => 'receivable-payable-aging-dashboard-saved-view-active-badge',
                    'default_badge' => 'receivable-payable-aging-dashboard-saved-view-default-badge',
                ],
            ],
            'sales-invoice-collection-follow-ups' => [
                'key' => 'sales-invoice-collection-follow-ups',
                'label' => 'تقرير متابعات تحصيل فواتير المبيعات',
                'view' => 'reports.sales-invoice-collection-follow-ups',
                'view_path' => 'resources/views/reports/sales-invoice-collection-follow-ups.blade.php',
                'index_route' => 'reports.sales-invoice-collection-follow-ups.index',
                'export_route' => 'reports.sales-invoice-collection-follow-ups.export',
                'saved_view_store_route' => 'reports.sales-invoice-collection-follow-ups.saved-views.store',
                'config_partial' => 'reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config',
                'config_partial_path' => 'resources/views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php',
                'hidden_fields' => [
                    'customer_id',
                    'follow_up_from',
                    'follow_up_to',
                ],
                'test_ids' => [
                    'section_card' => 'sales-invoice-collection-follow-ups-saved-views-selector',
                    'empty' => 'sales-invoice-collection-follow-ups-saved-views-empty',
                    'form_card' => 'sales-invoice-collection-follow-ups-save-view-card',
                    'form' => 'sales-invoice-collection-follow-ups-save-view-form',
                    'name_input' => 'sales-invoice-collection-follow-ups-saved-view-name-input',
                    'default_checkbox' => 'sales-invoice-collection-follow-ups-saved-view-default-checkbox',
                    'save_button' => 'sales-invoice-collection-follow-ups-save-view-button',
                    'list' => 'sales-invoice-collection-follow-ups-saved-views-list',
                    'item' => 'sales-invoice-collection-follow-ups-saved-view-item',
                    'open_link' => 'sales-invoice-collection-follow-ups-saved-view-open-link',
                    'active_badge' => 'sales-invoice-collection-follow-ups-saved-view-active-badge',
                    'default_badge' => 'sales-invoice-collection-follow-ups-saved-view-default-badge',
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
