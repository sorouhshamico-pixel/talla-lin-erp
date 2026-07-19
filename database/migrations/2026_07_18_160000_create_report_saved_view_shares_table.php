<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_saved_view_shares',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'report_saved_view_id'
                )
                    ->constrained('report_saved_views')
                    ->cascadeOnDelete();

                $table->foreignId('owner_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('recipient_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('permission', 10);
                $table->timestamps();

                $table->unique(
                    [
                        'report_saved_view_id',
                        'recipient_user_id',
                    ],
                    'report_saved_view_shares_view_recipient_unique'
                );

                $table->index(
                    'owner_user_id',
                    'report_saved_view_shares_owner_index'
                );
                $table->index(
                    'recipient_user_id',
                    'report_saved_view_shares_recipient_index'
                );
                $table->index(
                    'permission',
                    'report_saved_view_shares_permission_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'report_saved_view_shares'
        );
    }
};
