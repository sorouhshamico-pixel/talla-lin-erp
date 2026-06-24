<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name_ar');
            $table->string('name_en')->nullable();

            $table->string('commercial_registration')->nullable();
            $table->string('tax_number')->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->string('country', 2)->default('SA');
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            $table->string('currency', 3)->default('SAR');
            $table->string('timezone')->default('Asia/Riyadh');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('tax_number');
            $table->index('commercial_registration');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
