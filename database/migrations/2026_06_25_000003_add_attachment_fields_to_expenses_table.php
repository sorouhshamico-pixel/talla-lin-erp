<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('expenses', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('expenses', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            if (Schema::hasColumn('expenses', 'attachment_original_name')) {
                $table->dropColumn('attachment_original_name');
            }

            if (Schema::hasColumn('expenses', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
