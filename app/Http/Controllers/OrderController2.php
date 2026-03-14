<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Constants\AppConstants;
use App\Providers\FileValidationProvider;
use App\Providers\PrintConfigurationProvider;
use DeepCopy\f002\A;
use Illuminate\Validation\Rule;

class OrderController2 extends Controller
{
    // here I need a patch api to delete copys
    //DELETE /v2/order/copy/{id}
    public function deleteCopy(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $validator = app('validator')->make($request->all(), [
            'filename' => 'required|string',
            'copy_index' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filename = $request->input('filename');
        $copyIndex = $request->input('copy_index');

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (count($documents[$filename]['copies']) < 2) {
            return response()->json(['message' => 'Cannot delete the last copy.'], 400);
        }

        if (!isset($documents[$filename]['copies'][$copyIndex])) {
            return response()->json(['message' => 'Copy not found'], 404);
        }

        unset($documents[$filename]['copies'][$copyIndex]);

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Copy deleted successfully', 'order' => $order]);
    }
    //api to add a copy
    //POST /v2/order/copy/{id}
    public function addCopy(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }
        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        $validator = app('validator')->make($request->all(), [
            'filename' => 'required|string',
            'copy' => 'required|array',
            'copy.orientation_id' => ['required', 'integer', Rule::in($orientationIds)],
            'copy.mode_id' => 'required|integer|exists:print_modes,id',
            'copy.pages' => 'required|string',
            'copy.paper_size_id' => 'required|integer|exists:paper_sizes,id',
            'copy.number_of_copies' => 'required|integer|min:1',
            'copy.lamination' => 'required|boolean',
            'copy.comment' => 'nullable|string|max:255',
            'copy.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $filename = $validatedData['filename'];
        $newCopyData = $validatedData['copy'];

        if (!FileValidationProvider::validate($filename)) {
            return response()->json(['message' => 'Filename exceeds 70 characters'], 400);
        }

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (!FileValidationProvider::validatePages((int)$documents[$filename]['total_pages'], $newCopyData['pages'])) {
            return response()->json(['message' => "Document '{$filename}' has {$documents[$filename]['total_pages']} pages, but you are trying to print more."], 422);
        }

        $newCopy = [
            'orientation_id' => $newCopyData['orientation_id'],
            'mode_id' => $newCopyData['mode_id'],
            'paper_size_id' => $newCopyData['paper_size_id'],
            'number_of_copies' => $newCopyData['number_of_copies'],
            'pages' => $newCopyData['pages'],
            'lamination' => $newCopyData['lamination'],
            'comment' => $newCopyData['comment'] ?? null,
            'totalPrice' => $newCopyData['price'],
        ];

        $newIndex = !empty($documents[$filename]['copies']) ? max(array_keys($documents[$filename]['copies'])) + 1 : 1;

        $documents[$filename]['copies'][$newIndex] = $newCopy;

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Copy added successfully', 'order' => $order]);
    }

    public function addDocument(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        $validator = app('validator')->make($request->all(), [
            'file_base64' => 'required|string',
            'filename' => 'required|string',
            'serial_number' => 'required|integer',
            'total_pages' => 'required|integer',
            'configurations' => 'required|array|min:1',
            'configurations.*.orientation_id' => ['required', 'integer', Rule::in($orientationIds)],
            'configurations.*.mode_id' => 'required|integer|exists:print_modes,id',
            'configurations.*.pages' => 'required|string',
            'configurations.*.paper_size_id' => 'required|integer|exists:paper_sizes,id',
            'configurations.*.number_of_copies' => 'required|integer|min:1',
            'configurations.*.lamination' => 'required|boolean',
            'configurations.*.comment' => 'nullable|string|max:255',
            'configurations.*.price' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $filename = $validatedData['filename'];

        if (!FileValidationProvider::validate($filename)) {
            return response()->json(['message' => 'Filename exceeds 70 characters'], 400);
        }

        $documents = $order->documents;

        if (isset($documents[$filename])) {
            return response()->json(['message' => 'Document with this filename already exists'], 409);
        }

        $base64String = $validatedData['file_base64'];
        if (str_contains($base64String, ';base64,')) {
            $base64String = explode(';base64,', $base64String)[1];
        }
        $fileData = base64_decode($base64String, true);
        if ($fileData === false) {
            return response()->json(['message' => 'Invalid base64 string for file: ' . $filename], 400);
        }

        if ($error = $this->validateFileType($fileData, $filename)) {
            return response()->json(['message' => $error], 400);
        }

        $storageName = time() . '_' . Str::random(10) . '_' . $filename;
        $path = 'orders/' . $storageName;
        if (!Storage::disk(AppConstants::PRIVATE)->put($path, $fileData)) {
            return response()->json(['message' => 'Failed to save file'], 500);
        }

        $copies = [];
        foreach ($validatedData['configurations'] as $index => $config) {
            if (!FileValidationProvider::validatePages((int)$validatedData['total_pages'], $config['pages'])) {
                return response()->json(['message' => "Document '{$filename}' has {$validatedData['total_pages']} pages, but configuration " . ($index + 1) . " tries to print more."], 422);
            }

            $copies[$index + 1] = [
                'orientation_id' => $config['orientation_id'],
                'mode_id' => $config['mode_id'],
                'paper_size_id' => $config['paper_size_id'],
                'number_of_copies' => $config['number_of_copies'],
                'pages' => $config['pages'],
                'lamination' => $config['lamination'],
                'comment' => $config['comment'] ?? null,
                'totalPrice' => $config['price'],
            ];
        }

        $documents[$filename] = [
            'storage_path' => $path,
            'total_pages' => $validatedData['total_pages'],
            'copies' => $copies,
            'serial_number' => $validatedData['serial_number'],
        ];

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Document added successfully', 'order' => $order]);
    }

    public function deleteDocument(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $validator = app('validator')->make($request->all(), [
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filename = $request->input('filename');
        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (count($documents) < 2) {
            return response()->json(['message' => 'Cannot delete the last document.'], 400);
        }

        if (isset($documents[$filename]['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($documents[$filename]['storage_path'])) {
            Storage::disk(AppConstants::PRIVATE)->delete($documents[$filename]['storage_path']);
        }

        unset($documents[$filename]);

        uasort($documents, function ($a, $b) {
            return ($a['serial_number'] ?? 0) <=> ($b['serial_number'] ?? 0);
        });

        $serial = 1;
        foreach ($documents as $key => $doc) {
            $documents[$key]['serial_number'] = $serial++;
        }

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Document deleted successfully', 'order' => $order]);
    }

    //PATCH /v2/order/copy/{id}
    public function updateCopy(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        $validator = app('validator')->make($request->all(), [
            'filename' => 'required|string',
            'copy_index' => 'required|integer',
            'total_pages' => 'sometimes|integer',
            'copy' => 'required|array',
            'copy.orientation_id' => ['sometimes', 'integer', Rule::in($orientationIds)],
            'copy.mode_id' => 'sometimes|integer|exists:print_modes,id',
            'copy.pages' => 'sometimes|string',
            'copy.paper_size_id' => 'sometimes|integer|exists:paper_sizes,id',
            'copy.number_of_copies' => 'sometimes|integer|min:1',
            'copy.lamination' => 'sometimes|boolean',
            'copy.comment' => 'nullable|string|max:255',
            'copy.price' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $filename = $validatedData['filename'];
        $copyIndex = $validatedData['copy_index'];
        $copyUpdates = $validatedData['copy'];

        if (!FileValidationProvider::validate($filename)) {
            return response()->json(['message' => 'Filename exceeds 70 characters'], 400);
        }

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (isset($validatedData['total_pages'])) {
            $documents[$filename]['total_pages'] = $validatedData['total_pages'];
        }

        if (!isset($documents[$filename]['copies'][$copyIndex])) {
            return response()->json(['message' => 'Copy not found'], 404);
        }

        $existingCopy = $documents[$filename]['copies'][$copyIndex];
        
        $currentTotalPages = (int)$documents[$filename]['total_pages']; // Already updated above if present in request
        $pagesToCheck = isset($copyUpdates['pages']) ? $copyUpdates['pages'] : $existingCopy['pages'];
        if (!FileValidationProvider::validatePages($currentTotalPages, $pagesToCheck)) {
            return response()->json(['message' => "Document '{$filename}' has {$currentTotalPages} pages, but this copy tries to print more."], 422);
        }

        if (isset($copyUpdates['price'])) {
            $copyUpdates['totalPrice'] = $copyUpdates['price'];
            unset($copyUpdates['price']);
        }

        if (isset($copyUpdates['orientation_id'])) {
            unset($existingCopy['orientation']);
        }
        if (isset($copyUpdates['mode_id'])) {
            unset($existingCopy['mode']);
        }
        if (isset($copyUpdates['paper_size_id'])) {
            unset($existingCopy['size']);
        }

        $documents[$filename]['copies'][$copyIndex] = array_merge($existingCopy, $copyUpdates);

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Copy updated successfully', 'order' => $order]);
    }

    public function updateDocument(Request $request, string $orderId)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $validator = app('validator')->make($request->all(), [
            'filename' => 'required|string',
            'total_pages' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filename = $request->input('filename');
        $totalPages = $request->input('total_pages');

        if (!FileValidationProvider::validate($filename)) {
            return response()->json(['message' => 'Filename exceeds 70 characters'], 400);
        }

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (isset($documents[$filename]['copies'])) {
            foreach ($documents[$filename]['copies'] as $index => $copy) {
                if (!FileValidationProvider::validatePages((int)$totalPages, $copy['pages'])) {
                    return response()->json(['message' => "Cannot reduce pages to {$totalPages} because copy #{$index} is set to print more pages."], 422);
                }
            }
        }

        $documents[$filename]['total_pages'] = $totalPages;

        $totalPrice = 0;
        foreach ($documents as $document) {
            foreach ($document['copies'] as $copy) {
                $totalPrice += $copy['totalPrice'];
            }
        }

        $order->documents = $documents;
        $order->total_price = $totalPrice;
        $order->total_price += $order->additional_charge;
        $order->save();

        return response()->json(['message' => 'Document updated successfully', 'order' => $order]);
    }
    
}
