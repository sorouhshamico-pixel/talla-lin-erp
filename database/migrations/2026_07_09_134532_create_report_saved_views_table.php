<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 120);
            $table->string('name', 120);
            $table->json('filters');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'report_key', 'name'], 'report_saved_views_user_report_name_unique');
            $table->index(['user_id', 'report_key', 'is_default'], 'report_saved_views_user_report_default_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_saved_views');
    }
};
