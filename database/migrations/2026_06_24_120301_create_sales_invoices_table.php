<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('invoice_number');

            $table->enum('status', [
                'draft',
                'issued',
                'paid',
                'cancelled',
            ])->default('draft');

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');

            $table->string('currency', 3)->default('SAR');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->default(0);

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'payment_status']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
