<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewListPartialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_saved_view_list_partial_renders_active_and_default_badges(): void
    {
        $user = User::query()->firstOrFail();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض من partial',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales-invoice-aging.index', [
            'saved_view_id' => $savedView->id,
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="sales-invoice-aging-saved-views-list"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-item"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-open-link"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-active-badge"', false);
        $response->assertSee('data-testid="sales-invoice-aging-saved-view-default-badge"', false);
        $response->assertSee('saved_view_id=' . $savedView->id, false);
    }

    public function test_saved_view_list_partial_keeps_drilldown_empty_message(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.customer-sales-invoice-aging.drilldown'));

        $response->assertOk();
        $response->assertSee('data-testid="customer-aging-drilldown-saved-views-empty"', false);
        $response->assertSee('لا توجد عروض محفوظة لهذه التفاصيل حتى الآن.');
    }
}
