<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReportSavedViewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_user_can_save_named_report_view(): void
    {
        $user = User::query()->firstOrFail();

        $view = app(ReportSavedViewService::class)->save($user, 'sales-invoice-aging', 'متابعة المتأخرات', [
            'customer_id' => 1,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
            'empty_value' => '',
            'null_value' => null,
        ]);

        $this->assertInstanceOf(ReportSavedView::class, $view);
        $this->assertSame('sales-invoice-aging', $view->report_key);
        $this->assertSame('متابعة المتأخرات', $view->name);
        $this->assertFalse($view->is_default);

        $this->assertSame([
            'customer_id' => 1,
            'payment_status' => 'partial',
            'aging_bucket' => 'without_due_date',
        ], $view->fresh()->filters);
    }

    public function test_saving_same_name_updates_existing_report_view(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $first = $service->save($user, 'sales-invoice-aging', 'عرض التحصيل', [
            'payment_status' => 'partial',
        ]);

        $second = $service->save($user, 'sales-invoice-aging', 'عرض التحصيل', [
            'payment_status' => 'unpaid',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, ReportSavedView::query()->count());
        $this->assertSame([
            'payment_status' => 'unpaid',
        ], $second->fresh()->filters);
    }

    public function test_only_one_default_saved_view_per_user_and_report(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $first = $service->save($user, 'sales-invoice-aging', 'عرض أول', [
            'payment_status' => 'partial',
        ], true);

        $second = $service->save($user, 'sales-invoice-aging', 'عرض ثاني', [
            'payment_status' => 'unpaid',
        ], true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame($second->id, $service->getDefault($user, 'sales-invoice-aging')?->id);
    }

    public function test_user_can_list_saved_views_for_specific_report(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $service->save($user, 'sales-invoice-aging', 'مبيعات', [
            'payment_status' => 'partial',
        ]);

        $service->save($user, 'supplier-purchase-invoice-aging', 'موردين', [
            'aging_bucket' => 'overdue_1_30',
        ]);

        $views = $service->list($user, 'sales-invoice-aging');

        $this->assertCount(1, $views);
        $this->assertSame('مبيعات', $views->first()->name);
    }

    public function test_user_can_delete_saved_view(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $view = $service->save($user, 'sales-invoice-aging', 'حذف لاحق', [
            'payment_status' => 'partial',
        ]);

        $service->delete($user, $view->id);

        $this->assertDatabaseMissing('report_saved_views', [
            'id' => $view->id,
        ]);
    }

    public function test_report_key_and_name_are_required(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportSavedViewService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->save($user, '', '', []);
    }
}
