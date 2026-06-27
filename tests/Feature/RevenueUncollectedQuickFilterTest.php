<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueUncollectedQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_uncollected_quick_filter_link_preserving_current_filters(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)->get(route('revenues.index', [
            'branch_id' => $branch->id,
            'archive_status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee('عرض الإيرادات غير المحصلة');
        $response->assertSee('revenue-uncollected-quick-filter', false);

        $html = html_entity_decode($response->getContent());

        $this->assertStringContainsString('branch_id=' . $branch->id, $html);
        $this->assertStringContainsString('archive_status=active', $html);
        $this->assertStringContainsString('collection_status=uncollected', $html);
    }

    public function test_uncollected_quick_filter_query_shows_only_uncollected_revenues(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $visibleBranch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $hiddenBranch = Branch::query()
            ->where('company_id', $company->id)
            ->whereKeyNot($visibleBranch->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'إيرادات فلتر غير محصل',
            'slug' => 'uncollected-quick-filter-revenues',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $visibleBranch, $category, 'Visible uncollected quick filter revenue', 1800, 'cash', false, null);
        $this->createRevenue($company, $visibleBranch, $category, 'Hidden collected quick filter revenue', 2500, 'cash', true, null);
        $this->createRevenue($company, $hiddenBranch, $category, 'Hidden branch uncollected quick filter revenue', 4000, 'cash', false, null);
        $this->createRevenue($company, $visibleBranch, $category, 'Hidden archived uncollected quick filter revenue', 5000, 'cash', false, now());

        $response = $this->actingAs($user)->get(route('revenues.index', [
            'branch_id' => $visibleBranch->id,
            'collection_status' => 'uncollected',
            'archive_status' => 'active',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('فلتر الإيرادات غير المحصلة مفعّل', $content);
        $this->assertStringContainsString('Visible uncollected quick filter revenue', $content);

        $this->assertStringNotContainsString('Hidden collected quick filter revenue', $content);
        $this->assertStringNotContainsString('Hidden branch uncollected quick filter revenue', $content);
        $this->assertStringNotContainsString('Hidden archived uncollected quick filter revenue', $content);
    }

    private function createRevenue(
        Company $company,
        Branch $branch,
        RevenueCategory $category,
        string $description,
        float $amount,
        string $collectionMethod,
        bool $isCollected,
        mixed $archivedAt
    ): Revenue {
        return Revenue::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'revenue_category_id' => $category->id,
            'code' => 'REV-QUICK-' . uniqid(),
            'revenue_date' => '2026-06-27',
            'description' => $description,
            'amount' => $amount,
            'tax_amount' => 0,
            'collection_method' => $collectionMethod,
            'is_collected' => $isCollected,
            'archived_at' => $archivedAt,
        ]);
    }
}
