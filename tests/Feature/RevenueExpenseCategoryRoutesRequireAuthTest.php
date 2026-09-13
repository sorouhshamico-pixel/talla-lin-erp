<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueExpenseCategoryRoutesRequireAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These routes previously sat outside the "auth" middleware group in
     * routes/web.php and were reachable without logging in. Guest requests
     * must now be redirected to the login page instead of reaching the
     * controller.
     */
    public function test_guests_are_redirected_to_login_for_previously_unprotected_routes(): void
    {
        $getRoutes = [
            'revenues.index',
            'revenues.create',
            'revenue-categories.index',
            'expenses.export-top-large',
            'expenses.export-large-unpaid',
            'expenses.export-large-paid',
        ];

        foreach ($getRoutes as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }

        $this->post(route('revenues.store'))->assertRedirect(route('login'));
        $this->post(route('revenue-categories.store'))->assertRedirect(route('login'));
        $this->patch(route('expense-categories.toggle', ['expenseCategory' => 1]))->assertRedirect(route('login'));
        $this->delete(route('expense-categories.destroy', ['expenseCategory' => 1]))->assertRedirect(route('login'));
        $this->patch(route('revenue-categories.toggle', ['revenueCategory' => 1]))->assertRedirect(route('login'));
    }
}
