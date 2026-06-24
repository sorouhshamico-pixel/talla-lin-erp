<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('code');

            $table->enum('type', [
                'main',
                'branch',
                'returns',
                'damaged',
            ])->default('branch');

            $table->string('city')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
