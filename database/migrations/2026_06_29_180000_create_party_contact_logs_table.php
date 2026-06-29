<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_contact_logs', function (Blueprint $table) {
            $table->id();

            if (Schema::hasTable('companies')) {
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            }

            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }

            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('contact_type')->default('call');
            $table->text('summary');
            $table->date('contacted_at')->nullable();
            $table->date('follow_up_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'contacted_at']);
            $table->index(['supplier_id', 'contacted_at']);
            $table->index('follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_contact_logs');
    }
};
