<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('revenues')) {
            return;
        }

        Schema::create('revenues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revenue_category_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable()->unique();
            $table->date('revenue_date');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->string('collection_method')->default('cash');
            $table->boolean('is_collected')->default(true);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'revenue_date']);
            $table->index(['branch_id', 'revenue_date']);
            $table->index(['revenue_category_id', 'revenue_date']);
            $table->index(['collection_method', 'is_collected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
