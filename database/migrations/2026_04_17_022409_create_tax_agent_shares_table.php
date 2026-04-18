<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tax_agent_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique();
            $table->integer('year');
            $table->string('label')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_agent_shares');
    }
};