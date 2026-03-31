<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('deduction_type')->default('NOT_DEDUCTIBLE')->after('is_active');
            $table->decimal('annual_limit', 10, 2)->nullable()->after('deduction_type');
            $table->text('description')->nullable()->after('annual_limit');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['deduction_type', 'annual_limit', 'description']);
        });
    }
};