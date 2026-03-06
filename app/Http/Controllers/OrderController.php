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

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Order::where('user_id', $user->id);

        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));

            $query->where(function ($q) use ($statuses) {
                $simpleStatuses = [];
                $maxHistoryDays = 0;

                foreach ($statuses as $status) {
                    if (preg_match('/^history(\d+)$/', $status, $matches)) {
                        $maxHistoryDays = max($maxHistoryDays, (int)$matches[1]);
                    } else {
                        $simpleStatuses[] = $status;
                    }
                }

                if (!empty($simpleStatuses)) {
                    $q->whereIn('status', $simpleStatuses);
                }

                if ($maxHistoryDays > 0) {
                    $q->orWhere(function ($sub) use ($maxHistoryDays) {
                        $sub->whereIn('status', [AppConstants::ORDER_STATUS_COMPLETED, AppConstants::ORDER_STATUS_CANCELLED])
                            ->where('created_at', '>=', now()->subDays($maxHistoryDays));
                    });
                }
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $orders = $query->latest()->paginate(min($perPage, 100));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        // Validate filename length
        $validator = app('validator')->make($request->all(), [
            'documents' => 'required|array|min:1',
            'documents.*.file_base64' => 'required|string',
            'documents.*.filename' => 'required|string|max:70',
            'documents.*.serial_number' => 'required|integer',
            'documents.*.configurations' => 'required|array|min:1',
            'documents.*.price' => ['required', 'numeric'],
            'documents.*.totalPages' => ['required'],
            'documents.*.configurations.*.orientation_id' => ['required', 'integer', Rule::in($orientationIds)],
            'documents.*.configurations.*.mode_id' => 'required|integer|exists:print_modes,id',
            'documents.*.configurations.*.pages' => ['required', 'string', 'regex:/^((All)|(\d+(\s*-\s*\d+)?(\s*,\s*\d+(\s*-\s*\d+)?)*))$/i'],
            'documents.*.configurations.*.paper_size_id' => 'required|integer|exists:paper_sizes,id',
            'documents.*.configurations.*.number_of_copies' => 'required|integer|min:1',
            'documents.*.configurations.*.comment' => 'nullable|string|max:255',
            'documents.*.configurations.*.price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $validator->validated();

        $orderDocuments = [];

        foreach ($data['documents'] as $doc) {
            if (!FileValidationProvider::validate($doc['filename'])) {
                return response()->json(['message' => 'Filename exceeds 70 characters: ' . $doc['filename']], 400);
            }

            $base64String = $doc['file_base64'];

            // Strip data URI scheme if present (e.g. "data:application/pdf;base64,")
            if (str_contains($base64String, ';base64,')) {
                $base64String = explode(';base64,', $base64String)[1];
            }

            $fileData = base64_decode($base64String, true);

            if ($fileData === false) {
                return response()->json(['message' => 'Invalid base64 string for file: ' . $doc['filename']], 400);
            }

            if ($error = $this->validateFileType($fileData, $doc['filename'])) {
                return response()->json(['message' => $error], 400);
            }

            $storageName = time() . '_' . Str::random(10) . '_' . $doc['filename'];
            $path = 'orders/' . $storageName;
            //php artisan storage:link
            if (!Storage::disk(AppConstants::PRIVATE)->put($path, $fileData)) {
                return response()->json(['message' => 'Failed to save file'], 500);
            }

            $copies = [];
            foreach ($doc['configurations'] as $index => $config) {

                if (!FileValidationProvider::validatePages((int)$doc['totalPages'], $config['pages'])) {
                    return response()->json(['message' => "Document '{$doc['filename']}' has {$doc['totalPages']} pages, but configuration " . ($index + 1) . " tries to print more."], 422);
                }

                $copies[$index + 1] = [
                    'orientation_id' => $config['orientation_id'],
                    'mode_id' => $config['mode_id'],
                    'paper_size_id' => $config['paper_size_id'],
                    'number_of_copies' => $config['number_of_copies'],
                    'pages' => $config['pages'],
                    'comment' => $config['comment'] ?? null,
                    'totalPrice' => $config['price'],
                ];
            }

            $orderDocuments[$doc['filename']] = [
                'storage_path' => $path,
                'total_pages' => $doc['totalPages'],
                'copies' => $copies,
                'serial_number' => $doc['serial_number'],
            ];
        }

        uasort($orderDocuments, function ($a, $b) {
            return ($a['serial_number'] ?? 0) <=> ($b['serial_number'] ?? 0);
        });

        $serial = 1;
        foreach ($orderDocuments as $key => $doc) {
            $orderDocuments[$key]['serial_number'] = $serial++;
        }

        $order = Order::create([
            'user_id' => $user->id,
            'documents' => $orderDocuments,
            'total_price' => $request->input('total_price'),
            'status' => AppConstants::ORDER_STATUS_PENDING,
            'payments' => (object)[],
            'comment' => $request->input('comment'),
        ]);

        return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot edit order that is not pending'], 400);
        }

        $orientationIds = PrintConfigurationProvider::getValidOrientationIds();

        $validator = app('validator')->make($request->all(), [
            'documents' => 'sometimes|array|min:1',
            'documents.*.filename' => 'required|string|max:70',
            'documents.*.serial_number' => 'required|integer',
            'documents.*.file_base64' => 'nullable|string',
            'documents.*.storage_path' => 'nullable|string',
            'documents.*.configurations' => 'required|array|min:1',
            'documents.*.price' => ['required', 'numeric'],
            'documents.*.totalPages' => ['required'],
            'documents.*.configurations.*.orientation_id' => ['required', 'integer', Rule::in($orientationIds)],
            'documents.*.configurations.*.mode_id' => 'required|integer|exists:print_modes,id',
            'documents.*.configurations.*.pages' => ['required', 'string', 'regex:/^((All)|(\d+(\s*-\s*\d+)?(\s*,\s*\d+(\s*-\s*\d+)?)*))$/i'],
            'documents.*.configurations.*.paper_size_id' => 'required|integer|exists:paper_sizes,id',
            'documents.*.configurations.*.number_of_copies' => 'required|integer|min:1',
            'documents.*.configurations.*.comment' => 'nullable|string|max:255',
            'documents.*.configurations.*.price' => 'required|numeric',
            'total_price' => 'sometimes|numeric',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $orderDocuments = [];

        if (isset($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                if (!FileValidationProvider::validate($doc['filename'])) {
                    return response()->json(['message' => 'Filename exceeds 70 characters: ' . $doc['filename']], 400);
                }

                $path = null;

                if (isset($doc['file_base64']) && !empty($doc['file_base64'])) {
                    $base64String = $doc['file_base64'];
                    if (str_contains($base64String, ';base64,')) {
                        $base64String = explode(';base64,', $base64String)[1];
                    }
                    $fileData = base64_decode($base64String, true);
                    if ($fileData === false) {
                        return response()->json(['message' => 'Invalid base64 string for file: ' . $doc['filename']], 400);
                    }
                    if ($error = $this->validateFileType($fileData, $doc['filename'])) {
                        return response()->json(['message' => $error], 400);
                    }
                    $storageName = time() . '_' . Str::random(10) . '_' . $doc['filename'];
                    $path = 'orders/' . $storageName;
                    if (!Storage::disk(AppConstants::PRIVATE)->put($path, $fileData)) {
                        return response()->json(['message' => 'Failed to save file'], 500);
                    }
                } elseif (isset($doc['storage_path'])) {
                    $path = $doc['storage_path'];
                } else {
                    return response()->json(['message' => 'File content missing for: ' . $doc['filename']], 400);
                }

                $copies = [];
                foreach ($doc['configurations'] as $index => $config) {
                    if (!FileValidationProvider::validatePages((int)$doc['totalPages'], $config['pages'])) {
                        return response()->json(['message' => "Document '{$doc['filename']}' has {$doc['totalPages']} pages, but configuration " . ($index + 1) . " tries to print more."], 422);
                    }

                    $copies[$index + 1] = [
                        'orientation_id' => $config['orientation_id'],
                        'mode_id' => $config['mode_id'],
                        'paper_size_id' => $config['paper_size_id'],
                        'number_of_copies' => $config['number_of_copies'],
                        'pages' => $config['pages'],
                        'comment' => $config['comment'] ?? null,
                        'totalPrice' => $config['price'],
                    ];
                }

                $orderDocuments[$doc['filename']] = [
                    'storage_path' => $path,
                    'total_pages' => $doc['totalPages'],
                    'copies' => $copies,
                    'serial_number' => $doc['serial_number'],
                ];
            }

            uasort($orderDocuments, function ($a, $b) {
                return ($a['serial_number'] ?? 0) <=> ($b['serial_number'] ?? 0);
            });

            $serial = 1;
            foreach ($orderDocuments as $key => $doc) {
                $orderDocuments[$key]['serial_number'] = $serial++;
            }
        }

        $updateData = [];
        if (isset($data['documents'])) {
            $updateData['documents'] = $orderDocuments;
        }
        if (array_key_exists('total_price', $data)) {
            $updateData['total_price'] = $data['total_price'];
        }
        if (array_key_exists('comment', $data)) {
            $updateData['comment'] = $data['comment'];
        }

        $order->update($updateData);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== AppConstants::ORDER_STATUS_PENDING) {
            return response()->json(['message' => 'Cannot delete order that is not pending'], 400);
        }

        foreach ($order->documents as $doc) {
            if (isset($doc['storage_path']) && Storage::disk(AppConstants::PRIVATE)->exists($doc['storage_path'])) {
                Storage::disk(AppConstants::PRIVATE)->delete($doc['storage_path']);
            }
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    public function getDocument(Request $request, string $orderId, string $filename)
    {
        $user = $request->user ?? $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $order = Order::where('user_id', $user->id)->where('id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $path = $documents[$filename]['storage_path'];

        if (!Storage::disk(AppConstants::PRIVATE)->exists($path)) {
            return response()->json(['message' => 'File not found on server'], 404);
        }

        return response()->download(Storage::disk(AppConstants::PRIVATE)->path($path), $filename);
    }

    public function adminGetDocument(Request $request, string $orderId, string $filename)
    {
        $order = Order::find($orderId);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $documents = $order->documents;

        if (!isset($documents[$filename])) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $path = $documents[$filename]['storage_path'];

        if (!Storage::disk(AppConstants::PRIVATE)->exists($path)) {
            return response()->json(['message' => 'File not found on server'], 404);
        }

        return response()->download(Storage::disk(AppConstants::PRIVATE)->path($path), $filename);
    }
}
