<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_attachments', function (Blueprint $table) {
            $table->id();

            if (Schema::hasTable('companies')) {
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            }

            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }

            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['supplier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_attachments');
    }
};
