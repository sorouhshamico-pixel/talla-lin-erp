<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_archive_button_for_revenue(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات قابلة للأرشفة',
            'slug' => 'archivable-revenues',
            'is_active' => true,
        ]);

        $revenue = $this->createRevenue($company, $branch, $category, 'إيراد يظهر زر الأرشفة');

        $response = $this->actingAs($user)->get(route('revenues.index'));

        $response->assertOk();
        $response->assertSee('إيراد يظهر زر الأرشفة');
        $response->assertSee('revenue-archive-button-' . $revenue->id, false);
        $response->assertSee('أرشفة');
    }

    public function test_user_can_archive_revenue_without_deleting_it_and_archived_revenue_is_hidden_from_index(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات مؤرشفة',
            'slug' => 'archived-revenues',
            'is_active' => true,
        ]);

        $revenue = $this->createRevenue($company, $branch, $category, 'إيراد سيتم أرشفته');

        $archiveResponse = $this->actingAs($user)->patch(route('revenues.archive', $revenue));

        $archiveResponse->assertRedirect(route('revenues.index'));

        $this->assertDatabaseHas('revenues', [
            'id' => $revenue->id,
            'description' => 'إيراد سيتم أرشفته',
        ]);

        $this->assertNotNull($revenue->fresh()->archived_at);

        $indexResponse = $this->actingAs($user)->get(route('revenues.index'));

        $indexResponse->assertOk();
        $indexResponse->assertDontSee('إيراد سيتم أرشفته');
    }

    private function createRevenue(
        Company $company,
        Branch $branch,
        RevenueCategory $category,
        string $description
    ): Revenue {
        return Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'code' => 'REV-ARCH-' . uniqid(),
            'revenue_date' => '2026-06-27',
            'description' => $description,
            'amount' => 3500,
            'tax_amount' => 0,
            'collection_method' => 'cash',
            'is_collected' => true,
        ]);
    }
}
