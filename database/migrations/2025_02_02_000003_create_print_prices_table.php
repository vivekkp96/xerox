<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_size_id')->constrained('paper_sizes')->onDelete('cascade');
            $table->foreignId('print_mode_id')->constrained('print_modes')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_prices');
    }
};