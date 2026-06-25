<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('expense_categories', 'is_active')) {
            Schema::table('expense_categories', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('expense_categories', 'is_active')) {
            Schema::table('expense_categories', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
