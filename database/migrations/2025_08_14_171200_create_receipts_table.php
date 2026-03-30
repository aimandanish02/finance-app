<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->enum('type', ['image', 'pdf']);
            $table->string('mime_type');
            $table->string('file_path');
            $table->boolean('is_indexed')->default(false); // For OCR processing later
            $table->text('ocr_text')->nullable(); // Stored text from OCR processing
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
