<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('revenues')) {
            return;
        }

        Schema::table('revenues', function (Blueprint $table): void {
            if (! Schema::hasColumn('revenues', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('notes')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('revenues')) {
            return;
        }

        Schema::table('revenues', function (Blueprint $table): void {
            if (Schema::hasColumn('revenues', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
