<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Providers\PaymentProvider;
use App\Constants\AppConstants;
use Illuminate\Support\Facades\Log;

class DeleteOrderPaymentImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:delete-payment-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete payment images for completed or cancelled orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $orders = Order::whereIn('status', [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])
                ->where('updated_at', '<', now()->subDays(30))
                ->get();
            $count = 0;
            foreach ($orders as $order) {
                PaymentProvider::deletePaymentImages($order->id);
                $count++;
            }
            Log::info("Deleted payment images for {$count} orders.");
        } catch (\Throwable $e) {
            Log::error('Error deleting payment images: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
