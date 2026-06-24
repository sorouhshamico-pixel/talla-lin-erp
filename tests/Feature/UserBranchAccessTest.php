<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_user_is_created_and_linked_to_all_initial_branches(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'name' => 'مدير النظام',
            'email' => 'admin@tallalin.local',
            'role' => 'owner',
            'is_active' => 1,
        ]);

        $admin = User::query()
            ->where('email', 'admin@tallalin.local')
            ->with(['branches', 'currentBranch'])
            ->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->isOwner());
        $this->assertNotNull($admin->currentBranch);
        $this->assertEquals('MAIN', $admin->currentBranch->code);

        $this->assertCount(2, $admin->branches);
        $this->assertTrue($admin->branches->contains('code', 'MAIN'));
        $this->assertTrue($admin->branches->contains('code', 'ONLINE'));

        $mainBranch = Branch::query()->where('code', 'MAIN')->first();
        $onlineBranch = Branch::query()->where('code', 'ONLINE')->first();

        $this->assertTrue($admin->canAccessBranch($mainBranch->id));
        $this->assertTrue($admin->canAccessBranch($onlineBranch->id));

        $this->assertDatabaseHas('user_branches', [
            'user_id' => $admin->id,
            'branch_id' => $mainBranch->id,
            'role' => 'owner',
            'is_primary' => 1,
            'can_access' => 1,
        ]);

        $this->assertDatabaseHas('user_branches', [
            'user_id' => $admin->id,
            'branch_id' => $onlineBranch->id,
            'role' => 'owner',
            'is_primary' => 0,
            'can_access' => 1,
        ]);
    }
}
