<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('delivery_note_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('delivery_notes')
                ->nullOnDelete();

            $table->unique('delivery_note_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique(['delivery_note_id']);
            $table->dropConstrainedForeignId('delivery_note_id');
        });
    }
};
