<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_contact_logs', function (Blueprint $table) {
            $table->timestamp('follow_up_completed_at')->nullable();
            $table->text('follow_up_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('party_contact_logs', function (Blueprint $table) {
            $table->dropColumn([
                'follow_up_completed_at',
                'follow_up_result',
            ]);
        });
    }
};
