<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchWarehousePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_branches_page(): void
    {
        $response = $this->get('/branches');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_view_warehouses_page(): void
    {
        $response = $this->get('/warehouses');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_owner_can_view_branches_page(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', 'admin@tallalin.local')
            ->firstOrFail();

        $response = $this->actingAs($admin)->get('/branches');

        $response->assertOk();
        $response->assertSee('الفروع');
        $response->assertSee('الفرع الرئيسي');
        $response->assertSee('المتجر الإلكتروني');
    }

    public function test_authenticated_owner_can_view_warehouses_page(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', 'admin@tallalin.local')
            ->firstOrFail();

        $response = $this->actingAs($admin)->get('/warehouses');

        $response->assertOk();
        $response->assertSee('المستودعات');
        $response->assertSee('المستودع الرئيسي');
        $response->assertSee('الفرع الرئيسي');
    }
}
