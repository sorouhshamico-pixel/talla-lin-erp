<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryNoteInvoiceLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_delivery_note_show_page_displays_convert_to_invoice_button_when_no_invoice_exists(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $response = $this->actingAs($user)->get('/delivery-notes/' . $deliveryNote->id);

        $response->assertOk();
        $response->assertSee('تحويل إلى فاتورة مبيعات');
        $response->assertSee('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice', false);
    }

    public function test_delivery_note_show_page_displays_linked_sales_invoice_when_invoice_exists(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $branch = Branch::query()->where('company_id', $deliveryNote->customer->company_id)->firstOrFail();

        $invoice = SalesInvoice::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $deliveryNote->customer_id,
            'delivery_note_id' => $deliveryNote->id,
            'user_id' => $user->id,
            'invoice_number' => 'INV-000001',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 2500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 2500,
            'paid_amount' => 0,
            'remaining_amount' => 2500,
            'issued_at' => now(),
            'notes' => 'فاتورة مرتبطة بسند التسليم',
        ]);

        $response = $this->actingAs($user)->get('/delivery-notes/' . $deliveryNote->id);

        $response->assertOk();
        $response->assertSee('فاتورة مرتبطة');
        $response->assertSee('INV-000001');
        $response->assertSee('/sales-invoices/' . $invoice->id, false);
        $response->assertDontSee('تحويل إلى فاتورة مبيعات');
    }

    public function test_non_delivered_delivery_note_show_page_does_not_display_convert_to_invoice_button(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('draft');

        $response = $this->actingAs($user)->get('/delivery-notes/' . $deliveryNote->id);

        $response->assertOk();
        $response->assertDontSee('تحويل إلى فاتورة مبيعات');
    }

    private function createDeliveryNote(string $status): DeliveryNote
    {
        $companyId = $this->createCompanyId();

        Branch::create([
            'company_id' => $companyId,
            'name' => 'فرع رابط فاتورة السند',
            'code' => 'DN-INV-LINK',
            'type' => 'main',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'is_main' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => 'عميل رابط فاتورة سند التسليم',
            'phone' => '0501112222',
            'email' => 'delivery-note-invoice-link@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'accepted',
            'total_amount' => 2500,
            'notes' => 'عرض سعر مرتبط برابط فاتورة سند التسليم',
        ]);

        $salesOrder = SalesOrder::create([
            'sales_order_number' => 'SO-000001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => 2500,
            'notes' => 'أمر بيع مرتبط برابط فاتورة سند التسليم',
        ]);

        return DeliveryNote::create([
            'delivery_note_number' => 'DN-000001',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'delivery_note_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'اختبار رابط فاتورة سند التسليم',
        ]);
    }

    private function createCompanyId(): int
    {
        $existingId = DB::table('companies')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $columns = DB::select('PRAGMA table_info(companies)');
        $data = [];

        foreach ($columns as $column) {
            $name = $column->name;
            $type = strtolower((string) $column->type);
            $isRequired = (int) $column->notnull === 1 && $column->dflt_value === null;

            if ($name === 'id') {
                continue;
            }

            if (in_array($name, ['created_at', 'updated_at'], true)) {
                $data[$name] = now();
                continue;
            }

            if (! $isRequired) {
                continue;
            }

            $data[$name] = match (true) {
                $name === 'name' || str_contains($name, 'company_name') => 'شركة اختبار',
                str_contains($name, 'email') => 'company@example.com',
                str_contains($name, 'phone') || str_contains($name, 'mobile') => '0500000000',
                str_contains($name, 'tax') => '300000000000003',
                str_contains($name, 'commercial') || str_contains($name, 'cr_number') => '1010000000',
                str_contains($name, 'user_id') => DB::table('users')->value('id') ?? User::factory()->create()->id,
                str_contains($name, 'is_') || str_contains($name, 'active') => true,
                str_contains($type, 'int') => 1,
                str_contains($type, 'real') || str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal') => 0,
                str_contains($type, 'date') => now()->toDateString(),
                default => 'test-' . $name,
            };
        }

        return (int) DB::table('companies')->insertGetId($data);
    }
}
