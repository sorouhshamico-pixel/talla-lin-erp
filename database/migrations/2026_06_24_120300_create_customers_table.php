<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'is_active']);
            $table->index('email');
            $table->index('tax_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
