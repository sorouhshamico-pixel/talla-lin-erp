<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'report_saved_view_share_activity_retention_executions',
            function (Blueprint $table): void {
                $table->id();
                $table->string('type', 32);
                $table->string('status', 32);
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('requested_days');
                $table->unsignedInteger('requested_chunk_size')->nullable();
                $table->unsignedBigInteger('candidate_count')->nullable();
                $table->unsignedBigInteger('deleted_count')->nullable();
                $table->timestamp('cutoff_at')->nullable();
                $table->unsignedBigInteger('duration_ms')->nullable();
                $table->string('failure_class')->nullable();
                $table->text('failure_message')->nullable();
                $table->json('context')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->index(['created_at', 'id']);
                $table->index(['type', 'status']);
                $table->index(['actor_user_id', 'created_at']);
                $table->index('started_at');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'report_saved_view_share_activity_retention_executions'
        );
    }
};
