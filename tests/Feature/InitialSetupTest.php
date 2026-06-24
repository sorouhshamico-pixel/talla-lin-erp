<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_company_branches_and_warehouse_are_created_successfully(): void
    {
        $this->seed();

        $this->assertDatabaseHas('companies', [
            'name_ar' => 'طلة لين',
            'currency' => 'SAR',
            'country' => 'SA',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('branches', [
            'code' => 'MAIN',
            'name' => 'الفرع الرئيسي',
            'type' => 'main',
            'is_main' => 1,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('branches', [
            'code' => 'ONLINE',
            'name' => 'المتجر الإلكتروني',
            'type' => 'online',
            'is_main' => 0,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('warehouses', [
            'code' => 'MAIN-WH',
            'name' => 'المستودع الرئيسي',
            'type' => 'main',
            'is_main' => 1,
            'is_active' => 1,
        ]);

        $company = Company::query()
            ->where('name_ar', 'طلة لين')
            ->with(['branches', 'warehouses'])
            ->first();

        $this->assertNotNull($company);
        $this->assertCount(2, $company->branches);
        $this->assertCount(1, $company->warehouses);

        $mainBranch = Branch::query()
            ->where('company_id', $company->id)
            ->where('code', 'MAIN')
            ->first();

        $onlineBranch = Branch::query()
            ->where('company_id', $company->id)
            ->where('code', 'ONLINE')
            ->first();

        $warehouse = Warehouse::query()
            ->where('company_id', $company->id)
            ->where('code', 'MAIN-WH')
            ->first();

        $this->assertNotNull($mainBranch);
        $this->assertNotNull($onlineBranch);
        $this->assertNotNull($warehouse);

        $this->assertEquals($company->id, $mainBranch->company_id);
        $this->assertEquals($company->id, $onlineBranch->company_id);
        $this->assertEquals($mainBranch->id, $warehouse->branch_id);
    }
}
