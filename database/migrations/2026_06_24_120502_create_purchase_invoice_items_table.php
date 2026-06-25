<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_invoice_id')
                ->constrained('purchase_invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->string('description');

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15.00);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_subtotal', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);

            $table->unsignedInteger('item_order')->default(1);

            $table->timestamps();

            $table->index('purchase_invoice_id');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
