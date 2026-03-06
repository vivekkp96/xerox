<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_orientations', function (Blueprint $table) {
            $table->text('svg')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('print_orientations', function (Blueprint $table) {
            $table->dropColumn('svg');
        });
    }
};
