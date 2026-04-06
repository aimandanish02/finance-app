<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['target_amount', 'monthly_percentage']);
            $table->decimal('target_amount', 10, 2)->nullable();
            $table->decimal('target_percentage', 5, 2)->nullable();
            $table->decimal('current_savings', 10, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('color')->default('#6366f1');
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};