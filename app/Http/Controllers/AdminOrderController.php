<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Notification;
use App\Providers\NotificationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Constants\AppConstants;
use App\Providers\PrintConfigurationProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user');

        if ($request->has('status')) {
            $status = $request->input('status');
            if (str_contains($status, ',')) {
                $query->whereIn('status', explode(',', $status));
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('email') && strlen($request->input('email')) >= 3) {
            $email = $request->input('email');
            $query->whereHas('user', function ($q) use ($email) {
                $q->where('email', 'like', "%{$email}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = $query->latest()->paginate(min($perPage, 100));

        return response()->json($orders);
    }

    public function show(string $id)
    {
        $order = Order::with('user')->findOrFail($id);

        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])) {
            return response()->json(['message' => 'Cannot update order that is already ' . AppConstants::ORDER_STATUS_COMPLETED . ' or ' . AppConstants::ORDER_STATUS_CANCELLED], 400);
        }

        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        $validator = app('validator')->make($request->all(), [
            'total_price' => 'sometimes|numeric',
            'documents' => 'sometimes|array',
            'documents.*' => 'nullable|array',
            'documents.*.total_pages' => 'sometimes|numeric',
            'documents.*.copies.*.orientation_id' => ['sometimes', 'integer', Rule::in($orientationIds)],
            'documents.*.copies' => 'sometimes|array',
            'documents.*.copies.*' => 'nullable|array',
            'documents.*.copies.*.mode_id' => 'sometimes|integer|exists:print_modes,id',
            'documents.*.copies.*.pages' => 'sometimes|string',
            'documents.*.copies.*.paper_size_id' => 'sometimes|integer|exists:paper_sizes,id',
            'documents.*.copies.*.number_of_copies' => 'sometimes|integer|min:1',
            'documents.*.copies.*.comment' => 'nullable|string|max:255',
            'documents.*.copies.*.totalPrice' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (isset($data['total_price'])) {
            $order->total_price = $data['total_price'];
        }

        if (isset($data['documents'])) {
            $currentDocuments = $order->documents ?? [];

            foreach ($data['documents'] as $filename => $docUpdates) {
                if ($docUpdates === null) {
                    if (isset($currentDocuments[$filename]['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($currentDocuments[$filename]['storage_path'])) {
                        Storage::disk(AppConstants::PRIVATE)->delete($currentDocuments[$filename]['storage_path']);
                    }
                    unset($currentDocuments[$filename]);
                    continue;
                }

                if (!isset($currentDocuments[$filename])) {
                    $currentDocuments[$filename] = ['copies' => []];
                }

                if (isset($docUpdates['file_base64']) && !empty($docUpdates['file_base64'])) {
                    if (isset($currentDocuments[$filename]['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($currentDocuments[$filename]['storage_path'])) {
                        Storage::disk(AppConstants::PRIVATE)->delete($currentDocuments[$filename]['storage_path']);
                    }

                    $base64String = $docUpdates['file_base64'];
                    if (str_contains($base64String, ';base64,')) {
                        $base64String = explode(';base64,', $base64String)[1];
                    }
                    $fileData = base64_decode($base64String, true);
                    if ($fileData === false) {
                        return response()->json(['message' => 'Invalid base64 string for file: ' . $filename], 400);
                    }
                    $storageName = time() . '_' . Str::random(10) . '_' . $filename;
                    $path = 'orders/' . $storageName;
                    if (!Storage::disk(AppConstants::PRIVATE)->put($path, $fileData)) {
                        return response()->json(['message' => 'Failed to save file'], 500);
                    }
                    $currentDocuments[$filename]['storage_path'] = $path;
                }

                if (isset($docUpdates['total_pages'])) {
                    $currentDocuments[$filename]['total_pages'] = $docUpdates['total_pages'];
                }

                if (isset($docUpdates['copies'])) {
                    foreach ($docUpdates['copies'] as $copyIndex => $copyUpdates) {
                        if ($copyUpdates === null) {
                            unset($currentDocuments[$filename]['copies'][$copyIndex]);
                            continue;
                        }
                        $existingCopy = $currentDocuments[$filename]['copies'][$copyIndex] ?? [];
                        if (isset($copyUpdates['orientation_id'])) {
                            unset($existingCopy['orientation']);
                        }
                        if (isset($copyUpdates['mode_id'])) {
                            unset($existingCopy['mode']);
                        }
                        if (isset($copyUpdates['paper_size_id'])) {
                            unset($existingCopy['size']);
                        }
                        $currentDocuments[$filename]['copies'][$copyIndex] = array_merge($existingCopy, $copyUpdates);
                    }
                }
            }
            $order->documents = $currentDocuments;
        }

        $order->save();

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);

        foreach ($order->documents as $doc) {
            if (isset($doc['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($doc['storage_path'])) {
                Storage::disk(AppConstants::PRIVATE)->delete($doc['storage_path']);
            }
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function addPayment(Request $request, string $orderId)
    {
        $order = Order::findOrFail($orderId);
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
        $order->save();

        return response()->json([
            'message' => 'Payment details added successfully',
            'order' => $order,
        ]);
    }

    public function updatePayment(Request $request, string $orderId, string $paymentId)
    {
        $order = Order::findOrFail($orderId);

        $payments = $order->payments ?? [];
        if (!isset($payments[$paymentId])) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $validator = app('validator')->make($request->all(), [
            'payment_screenshot' => 'nullable|string',
            'reference_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentData = $payments[$paymentId];

        if ($request->has('payment_screenshot')) {
            if (!empty($paymentData['image']) && Storage::disk(AppConstants::PRIVATE)->exists($paymentData['image'])) {
                Storage::disk(AppConstants::PRIVATE)->delete($paymentData['image']);
            }

            if ($request->input('payment_screenshot')) {
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
            } else {
                $paymentData['image'] = null;
            }
        }

        if ($request->has('reference_number')) {
            $paymentData['reference_number'] = $request->input('reference_number');
        }

        $payments[$paymentId] = $paymentData;
        $order->payments = $payments;
        $order->save();

        return response()->json(['message' => 'Payment details updated successfully', 'order' => $order]);
    }

    public function deletePayment(string $orderId, string $paymentId)
    {
        $order = Order::findOrFail($orderId);
        if (in_array($order->status, [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])) {
            return response()->json(['message' => 'Cannot delete payment for order that is completed or cancelled'], 400);
        }
        $payments = $order->payments ?? [];
        if (!isset($payments[$paymentId])) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $paymentData = $payments[$paymentId];
        if (!empty($paymentData['image']) && Storage::disk(AppConstants::PRIVATE)->exists($paymentData['image'])) {
            Storage::disk(AppConstants::PRIVATE)->delete($paymentData['image']);
        }
        unset($payments[$paymentId]);
        $order->payments = $payments;
        $order->save();
        return response()->json(['message' => 'Payment deleted successfully', 'order' => $order]);
    }

    public function getPaymentScreenshot(string $orderId, string $paymentId)
    {
        $order = Order::findOrFail($orderId);

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

    public function updatePaymentStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $allowedStatuses = [
            AppConstants::ORDER_PAYMENT_STATUS_PENDING,
            AppConstants::ORDER_PAYMENT_STATUS_PAID,
            AppConstants::ORDER_PAYMENT_STATUS_PAYMENT_VERIFIED,
            AppConstants::ORDER_PAYMENT_STATUS_REFUNDED,
        ];
        $validator = app('validator')->make($request->all(), [
            'payment_status' => ['required', 'string', Rule::in($allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        if ($request->input('payment_status') == $order->payment_status) {
              return response()->json(['message' => 'Payment status cannot be changed from current status'], 422);
        }
        NotificationProvider::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'message' => "Your order #{$order->id} payment status has been updated to {$request->input('payment_status')}.",
            'user_read_flag' => false,
        ], true);
        $order->payment_status = $request->input('payment_status');
        $order->save();

        return response()->json(['message' => 'Order payment status updated successfully', 'order' => $order]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])) {
            return response()->json(['message' => 'Cannot update order that is already ' . AppConstants::ORDER_STATUS_COMPLETED . ' or ' . AppConstants::ORDER_STATUS_CANCELLED], 400);
        }

        $allowedStatuses = [
            AppConstants::ORDER_STATUS_PENDING,
            AppConstants::ORDER_STATUS_PROCESSING,
            AppConstants::ORDER_STATUS_COMPLETED,
            AppConstants::ORDER_STATUS_CANCELLED,
        ];

        $validator = app('validator')->make($request->all(), [
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->input('status') == $order->status) {
            return response()->json(['message' => 'Order status cannot be changed from current status'], 422);
        }

        $newStatus = $request->input('status');

        if (in_array($newStatus, [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])) {
            $this->deleteOrderFiles($order);
        }

        $order->status = $newStatus;
        $order->save();

        NotificationProvider::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'message' => "Your order #{$order->id} status has been updated to {$order->status}.",
            'user_read_flag' => false,
        ], true);

        return response()->json(['message' => 'Order status updated successfully', 'order' => $order]);
    }

    public function updateAdditionalInfo(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])) {
            return response()->json(['message' => 'Cannot update order that is already ' . AppConstants::ORDER_STATUS_COMPLETED . ' or ' . AppConstants::ORDER_STATUS_CANCELLED], 400);
        }

        $validator = app('validator')->make($request->all(), [
            'additional_charge' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
            'total_price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('remark', $data)) {
             if (!empty($data['remark']) && $order->remark !== $data['remark']) {
                NotificationProvider::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'message' => "Admin added a remark to your order #{$order->id}: " . $data['remark'],
                    'user_read_flag' => false,
                ], true);
            }
            $order->remark = $data['remark'];
        }

        if (array_key_exists('additional_charge', $data)) {
            $order->additional_charge = $data['additional_charge'];
        }
        
        $order->total_price = $data['total_price'];
        $order->save();

        return response()->json(['message' => 'Order details updated successfully', 'order' => $order]);
    }

    private function deleteOrderFiles(Order $order)
    {
        if (!empty($order->documents)) {
            foreach ($order->documents as $doc) {
                if (isset($doc['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($doc['storage_path'])) {
                    Storage::disk(AppConstants::PRIVATE)->delete($doc['storage_path']);
                }
            }
        }
    }
}
