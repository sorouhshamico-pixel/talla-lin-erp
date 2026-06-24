<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('تسجيل الدخول');
    }

    public function test_guest_cannot_view_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_owner_can_login_and_view_dashboard(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => 'admin@tallalin.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $admin = User::query()
            ->where('email', 'admin@tallalin.local')
            ->first();

        $this->assertAuthenticatedAs($admin);

        $dashboard = $this->get('/dashboard');

        $dashboard->assertOk();
        $dashboard->assertSee('لوحة التحكم');
        $dashboard->assertSee('طلة لين');
        $dashboard->assertSee('الفرع الرئيسي');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->seed();

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@tallalin.local',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
