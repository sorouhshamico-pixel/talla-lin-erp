<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('code')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);

            $table->enum('payment_method', [
                'cash',
                'card',
                'bank_transfer',
                'online',
                'other',
            ])->default('cash');

            $table->date('expense_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(true);

            $table->timestamps();

            $table->index('company_id');
            $table->index('branch_id');
            $table->index('expense_category_id');
            $table->index('expense_date');
            $table->index('payment_method');
            $table->index('is_paid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
