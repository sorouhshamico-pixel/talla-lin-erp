<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->string('quotation_number')->unique();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->date('quotation_date');
            $table->date('valid_until')->nullable();

            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('quotation_date');
            $table->index('valid_until');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
