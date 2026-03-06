<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Constants\AppConstants;

class PaymentController extends Controller
{
    public function getPaymentDetails(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $upiIdSetting = Setting::where('name', 'upi_id')->first();

        return response()->json([
            'order' => $order,
            'upi_qr_image' => asset('storage/upi/upi_admin.jpeg'),
            'upi_id' => $upiIdSetting ? $upiIdSetting->value : null,
        ]);
    }

    public function addPayment(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validator = app('validator')->make($request->all(), [
            'payment_screenshot' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$request->input('payment_screenshot') && !$request->input('reference_number')) {
            return response()->json(['message' => 'Either payment screenshot or reference number is required'], 422);
        }

        $paymentData = [
            'image' => null,
            'reference_number' => null,
        ];

        if ($request->input('payment_screenshot')) {
            $currentPayments = $order->payments ?? [];
            $imagePaymentsCount = 0;
            foreach ($currentPayments as $payment) {
                if (!empty($payment['image'])) {
                    $imagePaymentsCount++;
                }
            }

            if ($imagePaymentsCount >= 4) {
                return response()->json(['message' => 'You cannot upload more than 4 payment screenshots.'], 422);
            }

            $base64String = $request->input('payment_screenshot');
            if (str_contains($base64String, ';base64,')) {
                $base64String = explode(';base64,', $base64String)[1];
            }
            $fileData = base64_decode($base64String, true);

            if ($fileData === false) {
                return response()->json(['message' => 'Invalid base64 string for payment screenshot'], 400);
            }

            $filename = time() . '_' . Str::random(10) . '_payment.jpg';
            $path = 'orders/payments/' . $filename;

            if (Storage::disk(AppConstants::PRIVATE)->put($path, $fileData)) {
                $paymentData['image'] = $path;
            } else {
                return response()->json(['message' => 'Failed to save payment screenshot'], 500);
            }
        }

        if ($request->input('reference_number')) {
            $paymentData['reference_number'] = $request->input('reference_number');
        }

        $currentPayments = $order->payments ?? [];
        $nextIndex = !empty($currentPayments) ? max(array_keys($currentPayments)) + 1 : 1;

        $currentPayments[(string)$nextIndex] = $paymentData;
        $order->payments = $currentPayments;

        // If user uploaded payment screenshot or reference number, and current payment_status is 'pending', set to 'paid'
        if (
            ($request->input('payment_screenshot') || $request->input('reference_number')) &&
            ($order->payment_status === AppConstants::ORDER_PAYMENT_STATUS_PENDING)
        ) {
            $order->payment_status = AppConstants::ORDER_PAYMENT_STATUS_PAID;
        }

        $order->save();

        return response()->json([
            'message' => 'Payment details added successfully',
            'order' => $order,
            'upi_qr_image' => asset('storage/upi/upi_admin.jpeg')
        ]);
    }

    public function getPaymentScreenshot(Request $request, string $orderId, string $paymentId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $payments = $order->payments ?? [];

        if (!isset($payments[$paymentId]) || empty($payments[$paymentId]['image'])) {
            return response()->json(['message' => 'Payment screenshot not found'], 404);
        }

        $path = $payments[$paymentId]['image'];

        if (!Storage::disk(AppConstants::PRIVATE)->exists($path)) {
            return response()->json(['message' => 'File not found on server'], 404);
        }

        return response()->download(Storage::disk(AppConstants::PRIVATE)->path($path));
    }
}
