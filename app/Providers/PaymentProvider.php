<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use App\Constants\AppConstants;

class PaymentProvider
{
    public static function deletePaymentImages(string $orderId): void
    {
        $order = Order::find($orderId);

        if ($order && !empty($order->payments)) {
            foreach ($order->payments as $payment) {
                if (!empty($payment['image']) && Storage::disk(AppConstants::PRIVATE)->exists($payment['image'])) {
                    Storage::disk(AppConstants::PRIVATE)->delete($payment['image']);
                }
            }
        }
    }
}