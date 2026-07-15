<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewEditFiltersUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_edit_page_renders_filter_values_as_read_only(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل لتعديل الفلاتر',
            'filters' => [
                'customer_id' => '1',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.saved-views.edit', $savedView->id));

        $response->assertOk();
        $response->assertSee('data-testid="report-saved-view-edit-filter-list"', false);
        $response->assertSee('data-testid="report-saved-view-edit-filter-raw-value"', false);
        $response->assertSee('العميل');
        $response->assertSee('عميلة تجربة');
        $response->assertSee('1');
        $response->assertSee('شريحة العمر');
        $response->assertSee('بدون تاريخ استحقاق');
        $response->assertSee('without_due_date');

        $response->assertDontSee('data-testid="report-saved-view-edit-filter-inputs"', false);
        $response->assertDontSee('data-testid="report-saved-view-edit-filter-input"', false);
        $response->assertDontSee('name="filters[customer_id]"', false);
        $response->assertDontSee('name="filters[aging_bucket]"', false);
    }

    public function test_submitted_filter_payload_is_ignored_when_updating_saved_view(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل لتعديل الفلاتر',
            'filters' => [
                'customer_id' => '1',
                'aging_bucket' => 'without_due_date',
                'payment_status' => 'paid',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->patch(route('reports.saved-views.update', $savedView->id), [
            'name' => 'عرض بفلاتر محدثة',
            'filters' => [
                'customer_id' => '2',
                'aging_bucket' => 'not_due',
                'payment_status' => 'unpaid',
            ],
        ]);

        $response->assertRedirect(route('reports.saved-views.index'));

        $savedView->refresh();

        $this->assertSame('عرض بفلاتر محدثة', $savedView->name);
        $this->assertSame([
            'customer_id' => '1',
            'aging_bucket' => 'without_due_date',
            'payment_status' => 'paid',
        ], $savedView->filters);
    }

    public function test_empty_filter_values_are_ignored_and_existing_filters_are_preserved(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض قابل لتنظيف الفلاتر',
            'filters' => [
                'customer_id' => '1',
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $response = $this->actingAs($user)->patch(route('reports.saved-views.update', $savedView->id), [
            'name' => 'عرض بفلاتر منظفة',
            'filters' => [
                'customer_id' => '',
                'aging_bucket' => 'not_due',
            ],
        ]);

        $response->assertRedirect(route('reports.saved-views.index'));

        $savedView->refresh();

        $this->assertSame('عرض بفلاتر منظفة', $savedView->name);
        $this->assertSame([
            'customer_id' => '1',
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }
}
