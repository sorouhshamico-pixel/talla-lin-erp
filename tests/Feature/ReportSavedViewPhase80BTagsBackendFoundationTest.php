<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewTag;
use App\Models\User;
use App\Services\ReportSavedViewTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSavedViewPhase80BTagsBackendFoundationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_schema_model_and_routes_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_tags'
            )
        );
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_tag'
            )
        );

        $tag = new ReportSavedViewTag();

        foreach (
            [
                'user_id',
                'name',
                'normalized_name',
                'color',
            ] as $field
        ) {
            $this->assertContains(
                $field,
                $tag->getFillable()
            );
        }

        foreach ([
            'reports.saved-view-tags.store' =>
                'POST',
            'reports.saved-view-tags.update' =>
                'PATCH',
            'reports.saved-view-tags.destroy' =>
                'DELETE',
            'reports.saved-views.tags.sync' =>
                'PUT',
            'reports.saved-views.bulk-attach-tags' =>
                'POST',
            'reports.saved-views.bulk-detach-tags' =>
                'DELETE',
        ] as $name => $method) {
            $route = Route::getRoutes()
                ->getByName($name);

            $this->assertNotNull(
                $route,
                $name
            );
            $this->assertContains(
                $method,
                $route->methods()
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_normalization_is_user_scoped(): void
    {
        $service = app(
            ReportSavedViewTagService::class
        );
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $first = $service->create(
            $firstUser,
            '  متابعة   شهرية  ',
            '#aabbcc'
        );
        $same = $service->create(
            $firstUser,
            'متابعة شهرية',
            '#112233'
        );
        $other = $service->create(
            $secondUser,
            'متابعة شهرية'
        );

        $this->assertSame(
            $first->id,
            $same->id
        );
        $this->assertNotSame(
            $first->id,
            $other->id
        );
        $this->assertSame(
            'متابعة شهرية',
            $first->name
        );
        $this->assertSame(
            '#AABBCC',
            $first->color
        );
    }

    public function test_sync_and_bulk_actions_enforce_scope(): void
    {
        $service = app(
            ReportSavedViewTagService::class
        );
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $first = $this->savedView(
            $user,
            'First'
        );
        $second = $this->savedView(
            $user,
            'Second'
        );
        $foreign = $this->savedView(
            $otherUser,
            'Foreign'
        );

        $tag = $service->create(
            $user,
            'مهم'
        );
        $foreignTag = $service->create(
            $otherUser,
            'خاص'
        );

        $service->syncSavedViewTags(
            $user,
            $first,
            [
                $tag->id,
                $foreignTag->id,
            ]
        );

        $this->assertSame(
            [$tag->id],
            $first->tags()
                ->pluck('id')
                ->all()
        );

        $this->assertSame(
            2,
            $service->bulkAttach(
                $user,
                [
                    $first->id,
                    $second->id,
                    $foreign->id,
                ],
                [
                    $tag->id,
                    $foreignTag->id,
                ]
            )
        );

        $this->assertTrue(
            $second->tags()
                ->whereKey($tag->id)
                ->exists()
        );
        $this->assertFalse(
            $foreign->tags()->exists()
        );

        $this->assertSame(
            2,
            $service->bulkDetach(
                $user,
                [
                    $first->id,
                    $second->id,
                ],
                [$tag->id]
            )
        );

        $this->assertFalse(
            $first->tags()->exists()
        );
        $this->assertFalse(
            $second->tags()->exists()
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
