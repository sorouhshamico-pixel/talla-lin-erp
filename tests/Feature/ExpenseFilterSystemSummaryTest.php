<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseFilterSystemSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_filter_system_summary_is_rendered_in_expected_order_with_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'status' => 'paid',
            'date_from' => '',
            'date_to' => '2026-01-31',
            'page' => 9,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $statusBarPosition = strpos($content, 'data-testid="expense-page-status-bar"');
        $activeFilterAlertPosition = strpos($content, 'data-testid="expense-active-filter-alert"');
        $activeFilterCountPosition = strpos($content, 'data-testid="expense-active-filter-count-card"');
        $activeFilterLabelsPosition = strpos($content, 'data-testid="expense-active-filter-labels-card"');
        $clearAllFiltersPosition = strpos($content, 'data-testid="expense-clear-all-filters-card"');

        foreach ([
            $statusBarPosition,
            $activeFilterAlertPosition,
            $activeFilterCountPosition,
            $activeFilterLabelsPosition,
            $clearAllFiltersPosition,
        ] as $position) {
            $this->assertNotFalse($position);
        }

        $this->assertLessThan($activeFilterAlertPosition, $statusBarPosition);
        $this->assertLessThan($activeFilterCountPosition, $activeFilterAlertPosition);
        $this->assertLessThan($activeFilterLabelsPosition, $activeFilterCountPosition);
        $this->assertLessThan($clearAllFiltersPosition, $activeFilterLabelsPosition);

        $this->assertStringContainsString('data-page-active-filter-count="3"', $content);
        $this->assertStringContainsString('data-page-has-active-filters="yes"', $content);
        $this->assertStringContainsString('data-active-filter-alert-count="3"', $content);
        $this->assertStringContainsString('data-active-filter-count="3"', $content);

        $this->assertStringContainsString('data-filter-key="payment_method"', $content);
        $this->assertStringContainsString('data-filter-key="status"', $content);
        $this->assertStringContainsString('data-filter-key="date_to"', $content);

        $this->assertStringNotContainsString('data-filter-key="date_from"', $content);
        $this->assertStringNotContainsString('data-filter-key="page"', $content);

        $this->assertStringContainsString('طريقة الدفع', $content);
        $this->assertStringContainsString('نقدًا', $content);
        $this->assertStringContainsString('حالة المصروف', $content);
        $this->assertStringContainsString('مدفوع', $content);
        $this->assertStringContainsString('إلى تاريخ', $content);
        $this->assertStringContainsString('2026-01-31', $content);

        $this->assertStringContainsString('data-testid="expense-clear-all-filters"', $content);
        $this->assertStringContainsString('مسح كل فلاتر المصروفات', $content);
    }

    public function test_expense_filter_system_summary_stays_idle_without_real_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('expenses.index', [
            'payment_method' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'page' => 2,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('data-testid="expense-page-status-bar"', $content);
        $this->assertStringContainsString('بدون فلاتر نشطة', $content);
        $this->assertStringContainsString('data-page-active-filter-count="0"', $content);
        $this->assertStringContainsString('data-page-has-active-filters="no"', $content);

        $this->assertStringNotContainsString('data-testid="expense-active-filter-alert"', $content);
        $this->assertStringNotContainsString('تنبيه: توجد فلاتر نشطة', $content);

        $this->assertStringContainsString('data-testid="expense-active-filter-count-card"', $content);
        $this->assertStringContainsString('data-active-filter-count="0"', $content);
        $this->assertStringContainsString('data-testid="expense-active-filter-count">0</strong>', $content);

        $this->assertStringContainsString('data-testid="expense-active-filter-labels-card"', $content);
        $this->assertStringContainsString('data-testid="expense-no-active-filter-labels"', $content);
        $this->assertStringContainsString('لا توجد فلاتر مصروفات نشطة حاليًا', $content);

        $this->assertStringContainsString('data-testid="expense-clear-all-filters-card"', $content);
        $this->assertStringContainsString('data-testid="expense-clear-all-filters"', $content);

        $this->assertStringNotContainsString('data-filter-key="payment_method"', $content);
        $this->assertStringNotContainsString('data-filter-key="status"', $content);
        $this->assertStringNotContainsString('data-filter-key="date_from"', $content);
        $this->assertStringNotContainsString('data-filter-key="date_to"', $content);
        $this->assertStringNotContainsString('data-filter-key="page"', $content);
    }
}
