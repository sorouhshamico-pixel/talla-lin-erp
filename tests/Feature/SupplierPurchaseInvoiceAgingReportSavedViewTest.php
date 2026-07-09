<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPurchaseInvoiceAgingReportSavedViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_supplier_aging_report_shows_save_view_form(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'without_due_date',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="supplier-aging-save-view-form"', false);
        $response->assertSee(route('reports.supplier-purchase-invoice-aging.saved-views.store'), false);
        $response->assertSee('value="' . $supplier->id . '"', false);
        $response->assertSee('value="without_due_date"', false);
    }

    public function test_user_can_save_supplier_aging_filters_as_named_view(): void
    {
        $user = User::query()->firstOrFail();
        $supplier = Supplier::query()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('reports.supplier-purchase-invoice-aging.saved-views.store'), [
            'name' => 'متابعة ذمم الموردين',
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'without_due_date',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('reports.supplier-purchase-invoice-aging.index', [
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'without_due_date',
        ]));

        $savedView = ReportSavedView::query()->firstOrFail();

        $this->assertSame($user->id, $savedView->user_id);
        $this->assertSame('supplier-purchase-invoice-aging', $savedView->report_key);
        $this->assertSame('متابعة ذمم الموردين', $savedView->name);
        $this->assertTrue($savedView->is_default);
        $this->assertSame([
            'supplier_id' => $supplier->id,
            'aging_bucket' => 'without_due_date',
        ], $savedView->filters);
    }

    public function test_supplier_aging_saved_view_requires_name(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->from(route('reports.supplier-purchase-invoice-aging.index'))
            ->post(route('reports.supplier-purchase-invoice-aging.saved-views.store'), [
                'name' => '',
                'aging_bucket' => 'without_due_date',
            ]);

        $response->assertRedirect(route('reports.supplier-purchase-invoice-aging.index'));
        $response->assertSessionHasErrors('name');
    }
}
