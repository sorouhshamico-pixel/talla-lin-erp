<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerStatementLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_show_view_displays_customer_statement_link(): void
    {
        $customer = new Customer();

        $customer->forceFill([
            'id' => 2201,
            'name' => 'عميل كشف الحساب',
            'phone' => '0500000000',
            'email' => 'customer-statement-link@example.com',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'tax_number' => '300000000000001',
            'vat_number' => null,
            'commercial_registration' => '1010000000',
            'notes' => null,
            'is_active' => true,
        ]);

        $this->withViewErrors([])
            ->view('customers.show', [
                'customer' => $customer,
            ])
            ->assertSee('كشف حساب العميل')
            ->assertSee('data-testid="customers-statement-link"', false)
            ->assertSee('/customers/' . $customer->id . '/statement', false);
    }
}
