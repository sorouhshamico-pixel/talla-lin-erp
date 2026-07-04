<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_note_id')
                ->constrained('delivery_notes')
                ->cascadeOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('delivery_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
    }
};
