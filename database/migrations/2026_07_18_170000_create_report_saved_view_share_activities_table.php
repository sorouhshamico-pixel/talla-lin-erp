<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_saved_view_share_activities',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId(
                    'report_saved_view_share_id'
                )
                    ->nullable()
                    ->constrained(
                        'report_saved_view_shares'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'report_saved_view_id'
                )
                    ->nullable()
                    ->constrained(
                        'report_saved_views'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'actor_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'owner_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'recipient_user_id'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string(
                    'action',
                    40
                );

                $table->string(
                    'permission_before',
                    16
                )->nullable();

                $table->string(
                    'permission_after',
                    16
                )->nullable();

                $table->string(
                    'source_name_snapshot',
                    120
                )->nullable();

                $table->string(
                    'source_report_key_snapshot',
                    120
                )->nullable();

                $table->json('metadata')->nullable();
                $table->timestamp('created_at');

                $table->index([
                    'report_saved_view_share_id',
                    'created_at',
                ], 'rsv_share_activity_share_created_idx');

                $table->index([
                    'report_saved_view_id',
                    'created_at',
                ], 'rsv_share_activity_view_created_idx');

                $table->index([
                    'owner_user_id',
                    'created_at',
                ], 'rsv_share_activity_owner_created_idx');

                $table->index([
                    'recipient_user_id',
                    'created_at',
                ], 'rsv_share_activity_recipient_created_idx');

                $table->index([
                    'actor_user_id',
                    'created_at',
                ], 'rsv_share_activity_actor_created_idx');

                $table->index([
                    'action',
                    'created_at',
                ], 'rsv_share_activity_action_created_idx');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'report_saved_view_share_activities'
        );
    }
};
