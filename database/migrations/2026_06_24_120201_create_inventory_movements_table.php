<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->enum('type', [
                'opening_balance',
                'purchase',
                'sale',
                'return',
                'adjustment',
                'transfer_in',
                'transfer_out',
                'damage',
            ]);

            $table->enum('direction', [
                'in',
                'out',
            ]);

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();

            $table->string('reference_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'branch_id']);
            $table->index(['warehouse_id', 'product_variant_id']);
            $table->index(['type', 'direction']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
