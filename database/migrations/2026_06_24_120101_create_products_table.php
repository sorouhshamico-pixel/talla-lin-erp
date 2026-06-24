<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('sku');
            $table->text('description')->nullable();

            $table->enum('type', [
                'simple',
                'variable',
            ])->default('variable');

            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(15.00);

            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
