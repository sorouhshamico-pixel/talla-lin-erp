<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_saved_view_tag',
            function (Blueprint $table): void {
                $table->foreignId('report_saved_view_id')
                    ->constrained('report_saved_views')
                    ->cascadeOnDelete();

                $table->foreignId('report_saved_view_tag_id')
                    ->constrained('report_saved_view_tags')
                    ->cascadeOnDelete();

                $table->primary(
                    [
                        'report_saved_view_id',
                        'report_saved_view_tag_id',
                    ],
                    'report_saved_view_tag_primary'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('report_saved_view_tag');
    }
};
