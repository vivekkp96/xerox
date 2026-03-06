<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Constants\AppConstants;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Stores document details in the requested JSON format:
            // { "filename": { "mode": "BW", "pages": "1-5", "totalPrice": 12 } }
            $table->json('documents');
            $table->json('payments');
            
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->enum('status', [AppConstants::ORDER_STATUS_CANCELLED, AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_PENDING, AppConstants::ORDER_STATUS_PROCESSING])
                  ->default(AppConstants::ORDER_STATUS_PENDING);
            $table->enum('payment_status', [AppConstants::ORDER_PAYMENT_STATUS_PENDING, AppConstants::ORDER_PAYMENT_STATUS_PAID, AppConstants::ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED, AppConstants::ORDER_PAYMENT_STATUS_REFUNDED])
                  ->default(AppConstants::ORDER_PAYMENT_STATUS_PENDING);
            $table->decimal('additional_charge', 10, 2)->nullable()->default(0);
            $table->text('remark')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};