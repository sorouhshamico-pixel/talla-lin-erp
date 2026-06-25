<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_invoice_id')
                ->constrained('purchase_invoices')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);

            $table->enum('method', [
                'cash',
                'card',
                'bank_transfer',
                'online',
                'other',
            ])->default('cash');

            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('purchase_invoice_id');
            $table->index('user_id');
            $table->index('method');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_payments');
    }
};
