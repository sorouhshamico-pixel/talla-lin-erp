<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InitialSetupSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            [
                'name_ar' => 'طلة لين',
            ],
            [
                'name_en' => 'Talla Lin',
                'country' => 'SA',
                'city' => 'الرياض',
                'currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
            ]
        );

        $mainBranch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MAIN',
            ],
            [
                'name' => 'الفرع الرئيسي',
                'type' => 'main',
                'city' => 'الرياض',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $onlineBranch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'ONLINE',
            ],
            [
                'name' => 'المتجر الإلكتروني',
                'type' => 'online',
                'city' => 'الرياض',
                'is_main' => false,
                'is_active' => true,
            ]
        );

        Warehouse::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MAIN-WH',
            ],
            [
                'branch_id' => $mainBranch->id,
                'name' => 'المستودع الرئيسي',
                'type' => 'main',
                'city' => 'الرياض',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        $owner = User::query()->updateOrCreate(
            [
                'email' => 'admin@tallalin.local',
            ],
            [
                'name' => 'مدير النظام',
                'password' => 'password',
                'role' => 'owner',
                'current_branch_id' => $mainBranch->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $owner->branches()->sync([
            $mainBranch->id => [
                'company_id' => $company->id,
                'role' => 'owner',
                'is_primary' => true,
                'can_access' => true,
            ],
            $onlineBranch->id => [
                'company_id' => $company->id,
                'role' => 'owner',
                'is_primary' => false,
                'can_access' => true,
            ],
        ]);
    }
}
