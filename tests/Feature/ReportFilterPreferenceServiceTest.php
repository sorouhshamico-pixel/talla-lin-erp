<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserReportFilterPreference;
use App\Services\ReportFilterPreferenceService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportFilterPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_report_filter_preferences_table_is_available(): void
    {
        $this->assertTrue(Schema::hasTable('user_report_filter_preferences'));
        $this->assertTrue(Schema::hasColumn('user_report_filter_preferences', 'user_id'));
        $this->assertTrue(Schema::hasColumn('user_report_filter_preferences', 'report_key'));
        $this->assertTrue(Schema::hasColumn('user_report_filter_preferences', 'filters'));
    }

    public function test_report_filter_preference_service_stores_and_updates_filters(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'cash-flow-dashboard', [
            'branch_id' => 1,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'empty_value' => '',
            'null_value' => null,
        ]);

        $this->assertSame([
            'branch_id' => 1,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ], $service->get($user, 'cash-flow-dashboard'));

        $service->save($user, 'cash-flow-dashboard', [
            'branch_id' => 2,
            'date_to' => '2026-08-31',
        ]);

        $this->assertSame([
            'branch_id' => 2,
            'date_to' => '2026-08-31',
        ], $service->get($user, 'cash-flow-dashboard'));

        $this->assertSame(1, UserReportFilterPreference::query()->where('user_id', $user->id)->where('report_key', 'cash-flow-dashboard')->count());
    }

    public function test_report_filter_preferences_are_scoped_by_user_and_report_key(): void
    {
        $firstUser = User::query()->firstOrFail();
        $secondUser = User::factory()->create([
            'email' => 'report-preference-user@example.com',
        ]);

        $service = app(ReportFilterPreferenceService::class);

        $service->save($firstUser, 'cash-flow-dashboard', [
            'branch_id' => 1,
        ]);

        $service->save($firstUser, 'receivable-payable-aging-dashboard', [
            'branch_id' => 2,
        ]);

        $service->save($secondUser, 'cash-flow-dashboard', [
            'branch_id' => 3,
        ]);

        $this->assertSame(['branch_id' => 1], $service->get($firstUser, 'cash-flow-dashboard'));
        $this->assertSame(['branch_id' => 2], $service->get($firstUser, 'receivable-payable-aging-dashboard'));
        $this->assertSame(['branch_id' => 3], $service->get($secondUser, 'cash-flow-dashboard'));
    }

    public function test_report_filter_preference_service_merges_saved_and_request_filters(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'cash-flow-dashboard', [
            'branch_id' => 1,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        $this->assertSame([
            'branch_id' => 1,
            'date_from' => '2026-07-01',
            'date_to' => '2026-08-31',
        ], $service->merge($user, 'cash-flow-dashboard', [
            'date_to' => '2026-08-31',
            'ignored_blank' => '',
        ]));
    }

    public function test_user_report_filter_preferences_relation_works(): void
    {
        $user = User::query()->firstOrFail();
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'cash-flow-dashboard', [
            'branch_id' => 1,
        ]);

        $this->assertTrue($user->reportFilterPreferences()->where('report_key', 'cash-flow-dashboard')->exists());
    }
}
