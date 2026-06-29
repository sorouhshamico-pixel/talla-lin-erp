<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_notes', function (Blueprint $table) {
            $table->id();

            if (Schema::hasTable('companies')) {
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            }

            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }

            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();

            $table->text('note');
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['supplier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_notes');
    }
};
