<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenues_index_shows_csv_export_link_preserving_current_filters(): void
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
            'archive_status' => 'archived',
        ]));

        $response->assertOk();
        $response->assertSee('تصدير CSV');
        $response->assertSee('revenue-export-link', false);

        $html = html_entity_decode($response->getContent());

        $this->assertStringContainsString('branch_id=' . $branch->id, $html);
        $this->assertStringContainsString('archive_status=archived', $html);
    }

    public function test_revenue_csv_export_respects_active_filters(): void
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
            'name' => 'إيرادات تصدير CSV',
            'slug' => 'csv-export-revenues',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $visibleBranch, $category, 'CSV visible active revenue', 5000, 'cash', true, null);
        $this->createRevenue($company, $visibleBranch, $category, 'CSV hidden archived revenue', 7000, 'cash', true, now());
        $this->createRevenue($company, $visibleBranch, $category, 'CSV hidden bank revenue', 3000, 'bank_transfer', true, null);
        $this->createRevenue($company, $visibleBranch, $category, 'CSV hidden uncollected revenue', 2000, 'cash', false, null);
        $this->createRevenue($company, $hiddenBranch, $category, 'CSV hidden branch revenue', 9000, 'cash', true, null);

        $response = $this->actingAs($user)->get(route('revenues.export', [
            'branch_id' => $visibleBranch->id,
            'collection_method' => 'cash',
            'collection_status' => 'collected',
            'archive_status' => 'active',
        ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('CSV visible active revenue', $csv);
        $this->assertStringContainsString('نشط', $csv);
        $this->assertStringContainsString('محصل', $csv);
        $this->assertStringContainsString('نقدًا', $csv);

        $this->assertStringNotContainsString('CSV hidden archived revenue', $csv);
        $this->assertStringNotContainsString('CSV hidden bank revenue', $csv);
        $this->assertStringNotContainsString('CSV hidden uncollected revenue', $csv);
        $this->assertStringNotContainsString('CSV hidden branch revenue', $csv);
    }

    public function test_revenue_csv_export_respects_archived_filter(): void
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
            'name' => 'إيرادات مؤرشفة للتصدير',
            'slug' => 'archived-csv-export-revenues',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $branch, $category, 'CSV visible archived revenue', 6400, 'cash', true, now());
        $this->createRevenue($company, $branch, $category, 'CSV hidden active revenue', 4200, 'cash', true, null);

        $response = $this->actingAs($user)->get(route('revenues.export', [
            'branch_id' => $branch->id,
            'archive_status' => 'archived',
        ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('CSV visible archived revenue', $csv);
        $this->assertStringContainsString('مؤرشف', $csv);

        $this->assertStringNotContainsString('CSV hidden active revenue', $csv);
    }

    public function test_revenue_csv_export_resolves_correct_branch_and_category_names_per_row(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $user = User::query()->firstOrFail();

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->take(2)
            ->get();

        $this->assertGreaterThanOrEqual(2, $branches->count(), 'Need at least two branches for this test.');

        [$branchOne, $branchTwo] = $branches;

        $categoryOne = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'تصنيف الصف الأول',
            'slug' => 'csv-row-one-category',
            'is_active' => true,
        ]);

        $categoryTwo = RevenueCategory::query()->create([
            'company_id' => $company->id,
            'name' => 'تصنيف الصف الثاني',
            'slug' => 'csv-row-two-category',
            'is_active' => true,
        ]);

        $this->createRevenue($company, $branchOne, $categoryOne, 'CSV row one revenue', 1000, 'cash', true, null);
        $this->createRevenue($company, $branchTwo, $categoryTwo, 'CSV row two revenue', 2000, 'cash', true, null);

        $response = $this->actingAs($user)->get(route('revenues.export'));

        $response->assertOk();

        $csv = $response->streamedContent();
        $lines = array_filter(explode("\n", $csv));

        $rowOneLine = null;
        $rowTwoLine = null;

        foreach ($lines as $line) {
            if (str_contains($line, 'CSV row one revenue')) {
                $rowOneLine = $line;
            }

            if (str_contains($line, 'CSV row two revenue')) {
                $rowTwoLine = $line;
            }
        }

        $this->assertNotNull($rowOneLine);
        $this->assertNotNull($rowTwoLine);

        $this->assertStringContainsString($branchOne->name, $rowOneLine);
        $this->assertStringContainsString('تصنيف الصف الأول', $rowOneLine);
        $this->assertStringNotContainsString('تصنيف الصف الثاني', $rowOneLine);

        $this->assertStringContainsString($branchTwo->name, $rowTwoLine);
        $this->assertStringContainsString('تصنيف الصف الثاني', $rowTwoLine);
        $this->assertStringNotContainsString('تصنيف الصف الأول', $rowTwoLine);
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
            'code' => 'REV-CSV-' . uniqid(),
            'revenue_date' => '2026-06-27',
            'description' => $description,
            'amount' => $amount,
            'tax_amount' => 0,
            'collection_method' => $collectionMethod,
            'is_collected' => $isCollected,
            'reference_number' => 'CSV-REF',
            'notes' => 'CSV export test note',
            'archived_at' => $archivedAt,
        ]);
    }
}
