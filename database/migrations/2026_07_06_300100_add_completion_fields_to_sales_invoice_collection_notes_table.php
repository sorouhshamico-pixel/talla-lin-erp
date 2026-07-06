<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoice_collection_notes', function (Blueprint $table): void {
            $table->timestamp('completed_at')->nullable()->after('follow_up_at');
            $table->foreignId('completed_by_user_id')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->text('completion_note')->nullable()->after('completed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_collection_notes', function (Blueprint $table): void {
            $table->dropForeign(['completed_by_user_id']);
            $table->dropColumn([
                'completed_at',
                'completed_by_user_id',
                'completion_note',
            ]);
        });
    }
};
