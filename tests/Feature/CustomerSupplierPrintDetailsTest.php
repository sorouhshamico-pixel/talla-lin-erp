<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerSupplierPrintDetailsTest extends TestCase
{
    public function test_customer_show_view_has_print_button_and_print_styles(): void
    {
        $path = resource_path('views/customers/show.blade.php');

        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertIsString($content);
        $this->assertStringContainsString('data-testid="customers-print-button"', $content);
        $this->assertStringContainsString('onclick="window.print()"', $content);
        $this->assertStringContainsString('طباعة بيانات العميل', $content);
        $this->assertStringContainsString('@media print', $content);
        $this->assertStringContainsString('.no-print', $content);
    }

    public function test_supplier_show_view_has_print_button_and_print_styles(): void
    {
        $path = resource_path('views/suppliers/show.blade.php');

        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertIsString($content);
        $this->assertStringContainsString('data-testid="suppliers-print-button"', $content);
        $this->assertStringContainsString('onclick="window.print()"', $content);
        $this->assertStringContainsString('طباعة بيانات المورد', $content);
        $this->assertStringContainsString('@media print', $content);
        $this->assertStringContainsString('.no-print', $content);
    }
}
