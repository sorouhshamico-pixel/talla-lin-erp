<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Services\ReportSavedViewTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase80BTagsManagementLifecycleTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_tag_filter_uses_any_selected_tag(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $savedViewService = app(
            ReportSavedViewService::class
        );
        $user = User::factory()->create();

        $firstTag = $tagService->create(
            $user,
            'أول'
        );
        $secondTag = $tagService->create(
            $user,
            'ثان'
        );

        $first = $this->savedView(
            $user,
            'First'
        );
        $second = $this->savedView(
            $user,
            'Second'
        );
        $plain = $this->savedView(
            $user,
            'Plain'
        );

        $tagService->syncSavedViewTags(
            $user,
            $first,
            [$firstTag->id]
        );
        $tagService->syncSavedViewTags(
            $user,
            $second,
            [$secondTag->id]
        );

        $page = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'active',
                [
                    $firstTag->id,
                    $secondTag->id,
                ]
            );

        $this->assertEqualsCanonicalizing(
            [
                $first->id,
                $second->id,
            ],
            $page->getCollection()
                ->pluck('id')
                ->all()
        );
        $this->assertFalse(
            $page->getCollection()
                ->pluck('id')
                ->contains($plain->id)
        );
    }

    public function test_tag_filter_supports_status_modes(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $savedViewService = app(
            ReportSavedViewService::class
        );
        $user = User::factory()->create();

        $tag = $tagService->create(
            $user,
            'حالة'
        );
        $active = $this->savedView(
            $user,
            'Active'
        );
        $archived = $this->savedView(
            $user,
            'Archived'
        );

        $tagService->syncSavedViewTags(
            $user,
            $active,
            [$tag->id]
        );
        $tagService->syncSavedViewTags(
            $user,
            $archived,
            [$tag->id]
        );

        $savedViewService->archive(
            $user,
            $archived->id
        );

        $activePage = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'active',
                [$tag->id]
            );

        $archivedPage = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'archived',
                [$tag->id]
            );

        $allPage = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'all',
                [$tag->id]
            );

        $this->assertSame(
            [$active->id],
            $activePage->getCollection()
                ->pluck('id')
                ->all()
        );
        $this->assertSame(
            [$archived->id],
            $archivedPage->getCollection()
                ->pluck('id')
                ->all()
        );
        $this->assertEqualsCanonicalizing(
            [
                $active->id,
                $archived->id,
            ],
            $allPage->getCollection()
                ->pluck('id')
                ->all()
        );
    }

    public function test_foreign_tag_ids_are_ignored(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $savedViewService = app(
            ReportSavedViewService::class
        );
        $user = User::factory()->create();
        $other = User::factory()->create();

        $owned = $tagService->create(
            $user,
            'Owned'
        );
        $foreign = $tagService->create(
            $other,
            'Foreign'
        );
        $savedView = $this->savedView(
            $user,
            'Tagged'
        );

        $tagService->syncSavedViewTags(
            $user,
            $savedView,
            [$owned->id]
        );

        $page = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'active',
                [$foreign->id]
            );

        $this->assertSame(
            1,
            $page->total()
        );
    }

    public function test_duplicate_copies_tags(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $user = User::factory()->create();

        $tag = $tagService->create(
            $user,
            'منسوخ'
        );
        $savedView = $this->savedView(
            $user,
            'Original'
        );

        $tagService->syncSavedViewTags(
            $user,
            $savedView,
            [$tag->id]
        );

        $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.duplicate',
                    $savedView
                )
            )
            ->assertRedirect(
                route(
                    'reports.saved-views.index'
                )
            );

        $duplicate = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Original - نسخة')
            ->firstOrFail();

        $this->assertFalse(
            $duplicate->is_default
        );
        $this->assertTrue(
            $duplicate->isActive()
        );
        $this->assertTrue(
            $duplicate->tags()
                ->whereKey($tag->id)
                ->exists()
        );
    }

    public function test_archive_restore_and_delete_boundaries(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $savedViewService = app(
            ReportSavedViewService::class
        );
        $user = User::factory()->create();

        $tag = $tagService->create(
            $user,
            'دائم'
        );
        $savedView = $this->savedView(
            $user,
            'Lifecycle'
        );

        $tagService->syncSavedViewTags(
            $user,
            $savedView,
            [$tag->id]
        );

        $this->assertTrue(
            $savedViewService->archive(
                $user,
                $savedView->id
            )
        );

        $this->assertTrue(
            $savedView->tags()
                ->whereKey($tag->id)
                ->exists()
        );

        $this->assertTrue(
            $savedViewService->restore(
                $user,
                $savedView->id
            )
        );

        $this->assertTrue(
            $savedView->tags()
                ->whereKey($tag->id)
                ->exists()
        );

        $savedViewService->delete(
            $user,
            $savedView->id
        );

        $this->assertDatabaseMissing(
            'report_saved_view_tag',
            [
                'report_saved_view_id' =>
                    $savedView->id,
            ]
        );
    }

    private function savedView(
        User $user,
        string $name
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);
    }
}
