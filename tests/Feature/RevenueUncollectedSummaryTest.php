<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueUncollectedSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_uncollected_revenue_summary_shows_count_and_total_respecting_current_filters(): void
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
            'name' => 'إيرادات غير محصلة',
            'slug' => 'uncollected-summary-revenues',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $visibleBranch, $category, 'Visible uncollected revenue 1800', 1800, 'cash', false, null);
        $this->createRevenue($company, $visibleBranch, $category, 'Visible uncollected revenue 1200', 1200, 'cash', false, null);

        $this->createRevenue($company, $visibleBranch, $category, 'Hidden collected revenue 5000', 5000, 'cash', true, null);
        $this->createRevenue($company, $visibleBranch, $category, 'Hidden bank uncollected revenue 700', 700, 'bank_transfer', false, null);
        $this->createRevenue($company, $visibleBranch, $category, 'Hidden archived uncollected revenue 4000', 4000, 'cash', false, now());
        $this->createRevenue($company, $hiddenBranch, $category, 'Hidden branch uncollected revenue 9000', 9000, 'cash', false, null);

        $response = $this->actingAs($user)->get(route('revenues.index', [
            'branch_id' => $visibleBranch->id,
            'collection_method' => 'cash',
            'archive_status' => 'active',
        ]));

        $response->assertOk();

        $summaryHtml = $this->uncollectedSummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص الإيرادات غير المحصلة', $summaryHtml);
        $this->assertStringContainsString('revenue-uncollected-summary-count">2</strong>', $summaryHtml);
        $this->assertStringContainsString('3,000.00 ريال', $summaryHtml);

        $this->assertStringNotContainsString('8,000.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('3,700.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('16,700.00 ريال', $summaryHtml);
    }

    public function test_uncollected_revenue_summary_respects_archive_status_filter(): void
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
            'name' => 'إيرادات مؤرشفة غير محصلة',
            'slug' => 'archived-uncollected-summary-revenues',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $branch, $category, 'Active uncollected revenue hidden from archived summary', 1100, 'cash', false, null);
        $this->createRevenue($company, $branch, $category, 'Archived uncollected revenue visible in archived summary', 2200, 'cash', false, now());
        $this->createRevenue($company, $branch, $category, 'Archived collected revenue hidden from summary', 3300, 'cash', true, now());

        $response = $this->actingAs($user)->get(route('revenues.index', [
            'branch_id' => $branch->id,
            'archive_status' => 'archived',
        ]));

        $response->assertOk();

        $summaryHtml = $this->uncollectedSummaryHtml($response->getContent());

        $this->assertStringContainsString('revenue-uncollected-summary-count">1</strong>', $summaryHtml);
        $this->assertStringContainsString('2,200.00 ريال', $summaryHtml);

        $this->assertStringNotContainsString('3,300.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('6,600.00 ريال', $summaryHtml);
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
            'code' => 'REV-UNCOLLECTED-' . uniqid(),
            'revenue_date' => '2026-06-27',
            'description' => $description,
            'amount' => $amount,
            'tax_amount' => 0,
            'collection_method' => $collectionMethod,
            'is_collected' => $isCollected,
            'archived_at' => $archivedAt,
        ]);
    }

    private function uncollectedSummaryHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="revenue-uncollected-summary"';
        $endNeedle = '<div class="card">' . "\n" . '        <h2 style="margin-top:0;">فلترة الإيرادات</h2>';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Uncollected revenue summary section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Uncollected revenue summary section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }
}
