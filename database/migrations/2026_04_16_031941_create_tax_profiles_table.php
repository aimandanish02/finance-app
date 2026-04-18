<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('has_spouse')->default(false);
            $table->boolean('spouse_disabled')->default(false);
            $table->boolean('self_disabled')->default(false);
            $table->json('children')->nullable();
            $table->boolean('has_parents_medical')->default(false);
            $table->boolean('has_disabled_equipment')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};