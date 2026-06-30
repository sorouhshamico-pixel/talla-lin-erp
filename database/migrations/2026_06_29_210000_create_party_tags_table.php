<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('applies_to')->default('both')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['slug', 'applies_to']);
        });

        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'party_tag_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('party_tag_id')->nullable()->constrained('party_tags')->nullOnDelete();
            });
        }

        if (Schema::hasTable('suppliers') && ! Schema::hasColumn('suppliers', 'party_tag_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->foreignId('party_tag_id')->nullable()->constrained('party_tags')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'party_tag_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('party_tag_id');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'party_tag_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('party_tag_id');
            });
        }

        Schema::dropIfExists('party_tags');
    }
};
