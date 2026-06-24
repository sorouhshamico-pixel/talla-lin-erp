<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->enum('role', [
                'owner',
                'general_manager',
                'branch_manager',
                'accountant',
                'sales',
                'inventory',
                'viewer',
            ])->default('viewer');

            $table->boolean('is_primary')->default(false);
            $table->boolean('can_access')->default(true);

            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['user_id', 'can_access']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
    }
};
