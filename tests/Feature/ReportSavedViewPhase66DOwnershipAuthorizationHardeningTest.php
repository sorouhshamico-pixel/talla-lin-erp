<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase66DOwnershipAuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_66d_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66d-saved-view-management-ownership-authorization.json'));
        $this->assertFileExists(base_path('docs/phase-66d-saved-view-management-ownership-authorization.md'));
    }

    public function test_user_cannot_open_edit_page_for_another_users_saved_view(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView();

        $this->actingAs($user)
            ->get(route('reports.saved-views.edit', $otherSavedView->id))
            ->assertNotFound();
    }

    public function test_user_cannot_update_another_users_saved_view(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView([
            'name' => 'عرض مستخدم آخر',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->patch(route('reports.saved-views.update', $otherSavedView->id), [
                'name' => 'محاولة تعديل غير مصرح',
                'is_default' => '1',
                'filters' => [
                    'aging_bucket' => 'not_due',
                ],
            ])
            ->assertNotFound();

        $otherSavedView->refresh();

        $this->assertSame('عرض مستخدم آخر', $otherSavedView->name);
        $this->assertFalse($otherSavedView->is_default);
        $this->assertSame([
            'aging_bucket' => 'without_due_date',
        ], $otherSavedView->filters);
    }

    public function test_user_cannot_apply_another_users_saved_view(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView();

        $this->actingAs($user)
            ->get(route('reports.saved-views.apply', $otherSavedView->id))
            ->assertNotFound();
    }

    public function test_user_cannot_duplicate_another_users_saved_view(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView([
            'name' => 'عرض لا ينسخ',
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.duplicate', $otherSavedView->id))
            ->assertNotFound();

        $this->assertSame(1, ReportSavedView::query()->count());
        $this->assertDatabaseMissing('report_saved_views', [
            'user_id' => $user->id,
            'name' => 'عرض لا ينسخ - نسخة',
        ]);
    }

    public function test_user_cannot_make_another_users_saved_view_default(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView([
            'is_default' => false,
        ]);

        $ownSavedView = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض المستخدم الحالي',
            'filters' => [],
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('reports.saved-views.make-default', $otherSavedView->id))
            ->assertNotFound();

        $otherSavedView->refresh();
        $ownSavedView->refresh();

        $this->assertFalse($otherSavedView->is_default);
        $this->assertTrue($ownSavedView->is_default);
    }

    public function test_user_cannot_delete_another_users_saved_view(): void
    {
        [$user, $otherSavedView] = $this->userAndOtherSavedView();

        $this->actingAs($user)
            ->delete(route('reports.saved-views.destroy', $otherSavedView->id))
            ->assertNotFound();

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $otherSavedView->id,
            'user_id' => $otherSavedView->user_id,
        ]);
    }

    public function test_destroy_all_deletes_only_authenticated_users_saved_views(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownA = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض المستخدم الأول',
            'filters' => [],
            'is_default' => false,
        ]);

        $ownB = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'عرض المستخدم الثاني',
            'filters' => [
                'from_date' => '2026-01-01',
            ],
            'is_default' => false,
        ]);

        $other = ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مستخدم آخر',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reports.saved-views.destroy-all'))
            ->assertRedirect(route('reports.saved-views.index'));

        $this->assertDatabaseMissing('report_saved_views', [
            'id' => $ownA->id,
        ]);

        $this->assertDatabaseMissing('report_saved_views', [
            'id' => $ownB->id,
        ]);

        $this->assertDatabaseHas('report_saved_views', [
            'id' => $other->id,
            'user_id' => $otherUser->id,
            'name' => 'عرض مستخدم آخر',
        ]);
    }

    public function test_phase_66d_json_contract_documents_ownership_hardening(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66d-saved-view-management-ownership-authorization.json')),
            true
        );

        $this->assertSame('Phase 66D', $contract['phase']);
        $this->assertSame('Phase 66C clean', $contract['baseline']['phase']);
        $this->assertSame('1c1dc8c', $contract['baseline']['commit']);
        $this->assertSame('1233 passed / 10930 assertions', $contract['baseline']['previous_tests']);
        $this->assertFalse($contract['scope']['implementation_changes_expected']);

        foreach ([
            'edit',
            'update',
            'apply',
            'duplicate',
            'make_default',
            'destroy',
            'destroy_all',
        ] as $action) {
            $this->assertContains($action, $contract['protected_actions']);
        }

        $this->assertTrue($contract['ownership_contract']['cross_user_record_actions_return_not_found']);
        $this->assertTrue($contract['ownership_contract']['destroy_all_deletes_only_authenticated_user_records']);
        $this->assertTrue($contract['ownership_contract']['destroy_all_preserves_other_users_records']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{0: User, 1: ReportSavedView}
     */
    private function userAndOtherSavedView(array $overrides = []): array
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $savedView = ReportSavedView::query()->create(array_merge([
            'user_id' => $otherUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'عرض مستخدم آخر',
            'filters' => [
                'aging_bucket' => 'without_due_date',
            ],
            'is_default' => false,
        ], $overrides));

        return [$user, $savedView];
    }
}
