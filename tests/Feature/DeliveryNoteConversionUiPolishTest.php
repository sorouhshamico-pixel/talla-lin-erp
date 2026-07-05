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

class DeliveryNoteConversionUiPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_note_show_page_displays_conversion_confirmation_copy(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $response = $this->actingAs($user)->get('/delivery-notes/' . $deliveryNote->id);

        $response->assertOk();
        $response->assertSee('تحويل إلى فاتورة مبيعات');
        $response->assertSee('تأكيد التحويل إلى فاتورة مبيعات', false);
        $response->assertSee('سيتم إنشاء فاتورة مبيعات مرتبطة بهذا السند بعد التأكيد.');
    }

    public function test_delivery_note_conversion_redirects_with_success_message(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $deliveryNote->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)
            ->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $invoice = SalesInvoice::query()->first();

        $this->assertNotNull($invoice);

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
        $response->assertSessionHas('success', 'تم تحويل سند التسليم إلى فاتورة مبيعات بنجاح.');
    }

    private function createDeliveryNote(string $status): DeliveryNote
    {
        $companyId = $this->createCompanyId();

        Branch::create([
            'company_id' => $companyId,
            'name' => 'فرع تحسين تحويل سند التسليم',
            'code' => 'DN-INV-UI',
            'type' => 'main',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'is_main' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => 'عميل تحسين تحويل سند التسليم',
            'phone' => '0505556666',
            'email' => 'delivery-note-conversion-ui@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-UI-001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'accepted',
            'total_amount' => 2500,
            'notes' => 'عرض سعر لاختبار تحسين واجهة تحويل سند التسليم',
        ]);

        $salesOrder = SalesOrder::create([
            'sales_order_number' => 'SO-UI-001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => 2500,
            'notes' => 'أمر بيع لاختبار تحسين واجهة تحويل سند التسليم',
        ]);

        return DeliveryNote::create([
            'delivery_note_number' => 'DN-UI-001',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'delivery_note_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'اختبار تحسين واجهة تحويل سند التسليم',
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
