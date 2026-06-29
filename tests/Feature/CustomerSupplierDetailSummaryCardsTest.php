<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerSupplierDetailSummaryCardsTest extends TestCase
{
    public function test_customer_show_view_has_detail_summary_card(): void
    {
        $path = resource_path('views/customers/show.blade.php');

        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertIsString($content);
        $this->assertStringContainsString('data-testid="customers-detail-summary"', $content);
        $this->assertStringContainsString('اسم العميل', $content);
        $this->assertStringContainsString('{{ $customer->name }}', $content);
        $this->assertStringContainsString('{{ $customer->phone ?: \'-\' }}', $content);
        $this->assertStringContainsString('{{ $customer->city ?: \'-\' }}', $content);
        $this->assertStringContainsString('detail-summary', $content);
    }

    public function test_supplier_show_view_has_detail_summary_card(): void
    {
        $path = resource_path('views/suppliers/show.blade.php');

        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertIsString($content);
        $this->assertStringContainsString('data-testid="suppliers-detail-summary"', $content);
        $this->assertStringContainsString('اسم المورد', $content);
        $this->assertStringContainsString('{{ $supplier->name }}', $content);
        $this->assertStringContainsString('{{ $supplier->phone ?: \'-\' }}', $content);
        $this->assertStringContainsString('{{ $supplier->city ?: \'-\' }}', $content);
        $this->assertStringContainsString('detail-summary', $content);
    }
}
