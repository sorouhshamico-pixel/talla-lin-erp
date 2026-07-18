<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_saved_view_tags',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->string('name', 40);
                $table->string('normalized_name', 40);
                $table->string('color', 7)->nullable();
                $table->timestamps();

                $table->unique(
                    ['user_id', 'normalized_name'],
                    'report_saved_view_tags_user_normalized_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('report_saved_view_tags');
    }
};
