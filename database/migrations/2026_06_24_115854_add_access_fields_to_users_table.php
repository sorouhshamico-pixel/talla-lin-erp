<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();

            $table->enum('role', [
                'owner',
                'general_manager',
                'branch_manager',
                'accountant',
                'sales',
                'inventory',
                'viewer',
            ])->default('viewer');

            $table->foreignId('current_branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();

            $table->index('role');
            $table->index('is_active');
            $table->index('current_branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_branch_id']);
            $table->dropColumn([
                'phone',
                'role',
                'current_branch_id',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
